<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\Category; // Importa el modelo Category
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Barryvdh\DomPDF\Facade\Pdf; // Para generar PDF
use Illuminate\Support\Facades\Storage; // Para guardar PDF en storage
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Para QR en PDF (si lo necesitas aquí)
use App\Services\GoogleDriveService;
use Google\Service\Drive\DriveFile;

class TemplateController extends Controller
{
    protected $googleService; // <-- Asegúrate de que esta propiedad esté declarada

    public function __construct(GoogleDriveService $googleService) // <-- Inyecta el servicio aquí
    {
        $this->googleService = $googleService;
        $this->middleware('auth');
        $this->middleware('role:admin|permission:manage templates');
    }

    public function index(Request $request)
    {
        $query = Template::query();

        // --- Filtros ---
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        $filterType = $request->input('type');
        if ($filterType && in_array($filterType, ['docs', 'sheets'])) {
            $query->where('type', $filterType);
        }
        $filterCategory = $request->input('category_id');
        if ($filterCategory) {
            $query->where('category_id', $filterCategory);
        }
        $filterStatus = $request->input('status'); // 'active', 'inactive', 'trashed', 'with_trashed'
        if ($filterStatus) {
            if ($filterStatus === 'trashed') $query->onlyTrashed();
            elseif ($filterStatus === 'with_trashed') $query->withTrashed();
            elseif ($filterStatus === 'active') $query->where('is_active', true)->whereNull('deleted_at');
            elseif ($filterStatus === 'inactive') $query->where('is_active', false)->whereNull('deleted_at');
        } else {
            $query->where('is_active', true)->whereNull('deleted_at'); // Default: solo activas y no eliminadas
        }

        $templates = $query->orderBy('name')->paginate(10);
        $categories = Category::all(); // Para el filtro de categorías

        return view('admin.templates.index', [
            'templates' => $templates,
            'search_query' => $search,
            'selected_type' => $filterType,
            'selected_category_id' => $filterCategory,
            'selected_status' => $filterStatus,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        $categories = Category::all(); // Para el dropdown de categorías
        return view('admin.templates.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:templates,name'],
            'type' => ['required', 'in:docs,sheets'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
             // --- VALIDACIÓN PARA CAMPOS DINÁMICOS ---
             'dynamic_keys.*' => ['nullable', 'string', 'max:255'],
             'dynamic_values.*' => ['nullable', 'string'],
            'mapping_rules_json_raw' => ['nullable', 'json'],
            // --- VALIDACIÓN: O ID DE DRIVE O ARCHIVO SUBIDO ---
            'google_drive_id' => ['nullable', 'string', 'max:255'],
            'template_file' => ['nullable', 'file', 'max:25600'], // Max 25MB (ajusta según necesites)
            // Custom rule to ensure one is present if both are empty
            'required_id_or_file' => ['required_without_all:google_drive_id,template_file'],
        ]);

        // --- Lógica para obtener el Google Drive ID (desde input o subida) ---
        $googleDriveId = $request->input('google_drive_id');
        $uploadedFile = $request->file('template_file');

        if ($uploadedFile) {
            // Si se subió un archivo, subirlo a Google Drive y obtener el ID
            try {
                $googleDriveId = $this->uploadFileToGoogleDrive($uploadedFile, $request->type);
            } catch (\Exception $e) {
                return back()->withInput()->withErrors(['template_file' => 'Error al subir archivo a Google Drive: ' . $e->getMessage()]);
            }
        } elseif (!$googleDriveId) {
            // Si no se subió archivo y tampoco se proporcionó ID, esto no debería pasar por la validación required_without_all
            return back()->withInput()->withErrors(['google_drive_id' => 'Debe proporcionar un ID de Google Drive o subir un archivo.']);
        }
        // --- FIN Lógica de ID de Google Drive ---

        // Validar el enlace a Google Drive (ahora con el ID obtenido)
        try {
            $this->testGoogleDriveLink($googleDriveId, $request->type);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['google_drive_id' => 'Error al verificar Google Drive ID: ' . $e->getMessage()]);
        }

        // --- CONSTRUCCIÓN DEL JSON #1 (mapping_rules_json) ---
        $mappingRules = [];
        $keys = $request->input('dynamic_keys');
        $values = $request->input('dynamic_values');

        if ($keys && is_array($keys)) {
            foreach ($keys as $index => $key) {
                if (!empty($key)) { // Solo si la clave no está vacía
                    $mappingRules[$key] = $values[$index] ?? null; // Asigna el valor
                }
            }
        }
        DB::transaction(function () use ($request, $googleDriveId, $mappingRules) {
            $template = Template::create([
                'name' => $request->name,
                'google_drive_id' => $googleDriveId, // Usar el ID obtenido
                'type' => $request->type,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'mapping_rules_json' => $mappingRules, // Laravel guardará el array PHP como JSON
                'created_by_user_id' => Auth::id(),
            ]);

            $this->generateAndStorePdfPreview($template);

            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_created')
                ->log('creó la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla creada exitosamente.');
    }

    public function edit(Template $template)
    {
        $categories = Category::all();
        // No necesitamos json_encode($template->mapping_rules_json) aquí,
        // el Blade lo manejará directamente con el @forelse.
        return view('admin.templates.edit', compact('template', 'categories'));
    }

    public function update(Request $request, Template $template)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:templates,name,' . $template->id],
            'type' => ['required', 'in:docs,sheets'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            // --- VALIDACIÓN PARA CAMPOS DINÁMICOS ---
            'dynamic_keys.*' => ['nullable', 'string', 'max:255'],
            'dynamic_values.*' => ['nullable', 'string'],
            'mapping_rules_json_raw' => ['nullable', 'json'],
            
            // --- VALIDACIÓN: O ID DE DRIVE O ARCHIVO SUBIDO ---
            'google_drive_id' => ['nullable', 'string', 'max:255'],
            'template_file' => ['nullable', 'file', 'max:25600'], // Max 25MB
            // Al menos uno debe estar presente si ambos campos no están vacíos
            'required_id_or_file' => ['required_without_all:google_drive_id,template_file'],
        ]);

        // --- Lógica para obtener el Google Drive ID (desde input o subida) ---
        $googleDriveId = $request->input('google_drive_id');
        $uploadedFile = $request->file('template_file');

        if ($uploadedFile) {
            // Si se subió un archivo, subirlo a Google Drive y obtener el ID
            try {
                $googleDriveId = $this->uploadFileToGoogleDrive($uploadedFile, $request->type);
            } catch (\Exception $e) {
                return back()->withInput()->withErrors(['template_file' => 'Error al subir archivo a Google Drive: ' . $e->getMessage()]);
            }
        } elseif (!$googleDriveId) {
            // Si no se subió archivo y tampoco se proporcionó ID, esto no debería pasar por la validación
            return back()->withInput()->withErrors(['google_drive_id' => 'Debe proporcionar un ID de Google Drive o subir un archivo.']);
        }
        // --- FIN Lógica de ID de Google Drive ---

        // Validar el enlace a Google Drive (ahora con el ID obtenido)
        try {
            $this->testGoogleDriveLink($googleDriveId, $request->type);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['google_drive_id' => 'Error al verificar Google Drive ID: ' . $e->getMessage()]);
        }

        // --- CONSTRUCCIÓN DEL JSON #1 (mapping_rules_json) ---
        $mappingRules = [];
        $keys = $request->input('dynamic_keys');
        $values = $request->input('dynamic_values');

        if ($keys && is_array($keys)) {
            foreach ($keys as $index => $key) {
                if (!empty($key)) {
                    $mappingRules[$key] = $values[$index] ?? null;
                }
            }
        }

        DB::transaction(function () use ($request, $template, $googleDriveId, $mappingRules) {
            $template->update([
                'name' => $request->name,
                'google_drive_id' => $googleDriveId, // Usar el ID obtenido
                'type' => $request->type,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', false),
                'mapping_rules_json' => $mappingRules,
            ]);

            if ($template->isDirty('google_drive_id') || $template->isDirty('type')) {
                $this->generateAndStorePdfPreview($template);
            }

            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_updated')
                ->log('actualizó la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla actualizada exitosamente.');
    }

     // --- HELPER: Subir Archivo a Google Drive (CORREGIDO) ---
    /**
     * Sube un archivo local a Google Drive y retorna su ID.
     * @param \Illuminate\Http\UploadedFile $uploadedFile El archivo subido desde el Request.
     * @param string $templateType 'docs' o 'sheets' para determinar el mimeType de Google.
     * @return string El ID del archivo subido en Google Drive.
     * @throws \Exception Si la subida falla.
     */
    protected function uploadFileToGoogleDrive(\Illuminate\Http\UploadedFile $uploadedFile, string $templateType): string
    {
        $driveService = $this->googleService->getDriveService();

        $originalMimeType = $uploadedFile->getMimeType();
        $targetGoogleMimeType = null; // Default to no conversion

        // Determinar el MIME Type de Google para la conversión si aplica
        if ($templateType === 'docs' && $originalMimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') { // .docx a Google Doc
            $targetGoogleMimeType = 'application/vnd.google-apps.document';
        } elseif ($templateType === 'sheets' && $originalMimeType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') { // .xlsx a Google Sheet
            $targetGoogleMimeType = 'application/vnd.google-apps.spreadsheet';
        }
        // Para PDF, se sube como PDF. Si quieres convertirlo a Google Doc/Sheet editable, es más complejo.
        // Por ahora, lo subimos como el tipo original si no es DOCX/XLSX.

        $fileMetadata = new DriveFile([
            'name' => $uploadedFile->getClientOriginalName(),
            'parents' => [env('GOOGLE_TEMPLATES_FOLDER_ID')], // ID de la carpeta de plantillas
            'mimeType' => $targetGoogleMimeType ?: $originalMimeType, // Usar el mimeType de Google si hay conversión, sino el original
        ]);

        $content = file_get_contents($uploadedFile->getRealPath());

        $optParams = [
            'data' => $content,
            'mimeType' => $originalMimeType, // MIME type original del archivo subido
            'uploadType' => 'multipart', // Para archivos pequeños/medianos. Para muy grandes, 'resumable'.
            'fields' => 'id', // Solo necesitamos el ID
        ];

        // Para archivos más grandes, 'resumable' es preferido.
        // El cliente de Google API maneja la MediaFileUpload internamente para subidas resumibles.
        if ($uploadedFile->getSize() > (2 * 1024 * 1024)) { // Si el archivo es mayor de 2MB, usar resumable
            $optParams['uploadType'] = 'resumable';
        }

        // Realizar la subida
        $uploadedDriveFile = $driveService->files->create($fileMetadata, $optParams);

        $fileId = $uploadedDriveFile->getId();

        if (!$fileId) {
            throw new \Exception('No se pudo obtener el ID del archivo subido a Google Drive.');
        }

        return $fileId;
    }

    /**
     * Muestra la vista detallada de una plantilla (Función 1, 4, 7).
     * Incluye previsualización PDF, historial de actividad y documentos generados.
     * @param Template $template
     * @return \Illuminate\View\View
     */
    public function show(Template $template)
    {
        // Historial de Actividad de Plantilla (Función 4)
        $templateActivities = Activity::where('subject_type', Template::class)
                                      ->where('subject_id', $template->id)
                                      ->latest()
                                      ->paginate(10, ['*'], 'activities_page');

        // Documentos Generados a partir de esta plantilla (Función 7)
        $generatedDocs = $template->generatedDocuments()->latest()->paginate(10, ['*'], 'generated_docs_page');

        return view('admin.templates.show', compact('template', 'templateActivities', 'generatedDocs'));
    }

    /**
     * "Elimina" suavemente una plantilla.
     */
    public function destroy(Template $template)
    {
        DB::transaction(function () use ($template) {
            $template->delete(); // Soft delete
            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_deleted')
                ->log('eliminó (soft delete) la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla eliminada suavemente.');
    }

    /**
     * Restaura una plantilla eliminada suavemente.
     */
    public function restore($id)
    {
        $template = Template::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($template) {
            $template->restore();
            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_restored')
                ->log('restauró la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla restaurada exitosamente.');
    }

    /**
     * Elimina permanentemente una plantilla y su PDF de previsualización.
     * ¡CUIDADO! Romperá referencias en generated_documents.
     */
    public function forceDelete($id)
    {
        $template = Template::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($template) {
            // Eliminar el archivo PDF de previsualización si existe
            if ($template->pdf_file_path && Storage::disk('public')->exists($template->pdf_file_path)) {
                Storage::disk('public')->delete($template->pdf_file_path);
            }

            $template->forceDelete(); // Elimina permanentemente el registro
            activity()
                ->performedOn(null) // Sujeto nulo porque se va a borrar
                ->causedBy(Auth::user())
                ->event('template_force_deleted')
                ->log('eliminó PERMANENTEMENTE la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla eliminada permanentemente.');
    }

    /**
     * Duplica una plantilla existente (Función 5).
     * Copia la plantilla en Google Drive y crea un nuevo registro en DB.
     * @param Template $template
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate(Template $template)
    {
        try {
            $driveService = $this->googleService->getDriveService();

            // 1. Copiar la plantilla original en Google Drive
            $copy = new \Google\Service\Drive\DriveFile();
            $copy->setName($template->name . ' (Copia) - ' . now()->format('YmdHis'));
            $copiedFile = $driveService->files->copy($template->google_drive_id, $copy);
            $newGoogleDriveId = $copiedFile->getId();

            // 2. Crear un nuevo registro de plantilla en la DB con los datos duplicados
            DB::transaction(function () use ($template, $newGoogleDriveId) {
                $newTemplate = Template::create([
                    'name' => $template->name . ' (Copia)',
                    'google_drive_id' => $newGoogleDriveId,
                    'type' => $template->type,
                    'category_id' => $template->category_id,
                    'mapping_rules_json' => $template->mapping_rules_json,
                    'description' => $template->description . ' (Copia generada de ' . $template->name . ')',
                    'is_active' => false, // Por defecto, la copia está inactiva hasta que se revise
                    'created_by_user_id' => Auth::id(),
                ]);

                // Generar y guardar la copia PDF para la plantilla duplicada
                $this->generateAndStorePdfPreview($newTemplate);

                activity()
                    ->performedOn($newTemplate)
                    ->causedBy(Auth::user())
                    ->event('template_duplicated')
                    ->log('duplicó la plantilla: "' . $template->name . '" a "' . $newTemplate->name . '".');
            });

            return redirect()->route('admin.templates.index')->with('success', 'Plantilla duplicada exitosamente.');

        } catch (\Google\Service\Exception $e) {
            $errorDetails = json_decode($e->getMessage(), true);
            $message = $errorDetails['error']['message'] ?? $e->getMessage();
            Log::error('Error de API de Google al duplicar plantilla: ' . $message, ['template_id' => $template->id]);
            return back()->with('error', 'Error al duplicar la plantilla en Google Drive: ' . $message);
        } catch (\Exception $e) {
            Log::error('Error inesperado al duplicar plantilla: ' . $e->getMessage(), ['template_id' => $template->id]);
            return back()->with('error', 'Error inesperado al duplicar la plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Verifica si un Google Drive ID es válido y accesible (Función 6).
     * @param string $googleDriveId
     * @param string $type 'docs' o 'sheets'
     * @throws \Exception Si el ID no es válido o no es accesible.
     */
    protected function testGoogleDriveLink(string $googleDriveId, string $type): void
    {
        $driveService = $this->googleService->getDriveService();
        try {
            $file = $driveService->files->get($googleDriveId, ['fields' => 'mimeType,name']);
            // Opcional: Verificar que el mimeType coincida con el tipo esperado (Docs/Sheets)
            if ($type === 'docs' && $file->getMimeType() !== 'application/vnd.google-apps.document') {
                throw new \Exception('El ID de Google Drive no corresponde a un Google Doc.');
            }
            if ($type === 'sheets' && $file->getMimeType() !== 'application/vnd.google-apps.spreadsheet') {
                throw new \Exception('El ID de Google Drive no corresponde a un Google Sheet.');
            }
            // Puedes añadir una comprobación de permisos si quieres que sea accesible públicamente
            // $permissions = $driveService->permissions->listDocuments($googleDriveId, ['fields' => 'permissions(id,type,role)']);
            // $hasPublicAccess = false;
            // foreach ($permissions->getPermissions() as $permission) {
            //     if ($permission->getType() === 'anyone' && ($permission->getRole() === 'reader' || $permission->getRole() === 'writer')) {
            //         $hasPublicAccess = true;
            //         break;
            //     }
            // }
            // if (!$hasPublicAccess) {
            //     throw new \Exception('La plantilla no tiene permisos de acceso público (cualquiera con el enlace).');
            // }

        } catch (\Google\Service\Exception $e) {
            $errorDetails = json_decode($e->getMessage(), true);
            $message = $errorDetails['error']['message'] ?? $e->getMessage();
            if ($e->getCode() === 404) {
                throw new \Exception('El ID de Google Drive no existe o no es accesible.');
            }
            throw new \Exception('Error al verificar el ID de Google Drive: ' . $message);
        }
    }

    /**
     * Helper: Genera una copia PDF de la plantilla y la guarda en el storage (Función 1).
     * @param Template $template
     * @return void
     */
    protected function generateAndStorePdfPreview(Template $template): void
    {
        try {
            // 1. Obtener el contenido del documento de Google Drive
            $driveService = $this->googleService->getDriveService();
            $exportMimeType = ($template->type === 'docs') ? 'application/pdf' : 'application/pdf'; // Exportar ambos como PDF

            // Exportar el documento de Google Drive a PDF
            $response = $driveService->files->export($template->google_drive_id, $exportMimeType, ['alt' => 'media']);
            $pdfContent = $response->getBody()->getContents();

            // 2. Guardar el PDF en el storage
            $fileName = 'templates_pdf/' . $template->id . '.pdf';
            Storage::disk('public')->put($fileName, $pdfContent);

            // 3. Actualizar la ruta del PDF en la DB de la plantilla
            $template->pdf_file_path = $fileName;
            $template->save();

            Log::info("PDF de previsualización generado y guardado para plantilla ID: {$template->id}");

        } catch (\Google\Service\Exception $e) {
            $errorDetails = json_decode($e->getMessage(), true);
            $message = $errorDetails['error']['message'] ?? $e->getMessage();
            Log::error("Error al generar PDF de previsualización para plantilla ID: {$template->id}: {$message}");
            // No lanzar excepción para no detener el store/update principal, solo loggear
        } catch (\Exception $e) {
            Log::error("Error inesperado al generar PDF de previsualización para plantilla ID: {$template->id}: {$e->getMessage()}");
        }
    }
    
     /**
     * Sirve el archivo PDF de previsualización de una plantilla.
     * Esta es la URL a la que apuntará el iframe en el panel de admin.
     *
     * @param Template $template
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function servePdfPreview(Template $template)
    {
        // Asegurarse de que el archivo PDF existe en el storage
        if (!$template->pdf_file_path || !Storage::disk('public')->exists($template->pdf_file_path)) {
            Log::error("PDF de previsualización no encontrado para plantilla ID: {$template->id}, Path: {$template->pdf_file_path}");
            abort(404, 'PDF de previsualización no encontrado.');
        }

        // Obtiene la ruta física completa del archivo
        $path = Storage::disk('public')->path($template->pdf_file_path);

        // Retorna el archivo al navegador con las cabeceras correctas
        // Laravel automáticamente establecerá el Content-Type adecuado (application/pdf)
        return response()->file($path);
    }
}