<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\Template; // Modelo de plantilla
use App\Models\TemplatePrefilledData; // Modelo de datos prellenados
use App\Models\GeneratedDocument; // Modelo para registrar documentos generados
use App\Services\GoogleDriveService; // Tu servicio de Google API
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity; // Para logs
use Google\Service\Drive\Permission; // Para permisos de Drive
use Google\Service\Sheets\ValueRange; // Para Sheets

// Importaciones de Google Docs
use Google\Service\Docs\BatchUpdateDocumentRequest;
use Google\Service\Docs\Request as GoogleDocsRequest;

class DocumentGenerationController extends Controller
{
    protected $googleService;

    public function __construct(GoogleDriveService $googleService)
    {
        $this->googleService = $googleService;
        $this->middleware('auth');
        // Opcional: proteger los métodos con permisos más específicos (ej. usando spatie/laravel-permission)
        // $this->middleware('permission:generate documents');
    }

    /**
     * Helper para la lógica común de generación de documentos (copiar, prellenar, registrar).
     * Es llamado por generateBlank, generatePredefined y generateCustom.
     *
     * @param Template $template La instancia del modelo de plantilla.
     * @param array $dataForFilling Los datos asociativos a usar para prellenar (clave => valor).
     * @param string $visibilityStatus El estado de visibilidad deseado ('public_editable', 'public_viewable', 'private_restricted').
     * @return string La URL del documento generado.
     * @throws \Exception Si ocurre un error durante la generación.
     */
    protected function generateDocument(Template $template, array $dataForFilling, string $visibilityStatus = 'private_restricted'): string
    {
        try {
            $driveService = $this->googleService->getDriveService();
            $newDocTitle = $template->name . ' - ' . Auth::user()->rpe . ' - ' . now()->format('YmdHis');

            // 1. Copiar la plantilla
            $copy = new \Google\Service\Drive\DriveFile();
            $copy->setName($newDocTitle);
            $copiedFile = $driveService->files->copy($template->google_drive_id, $copy);
            $newGoogleDriveId = $copiedFile->getId();

            // 2. Prellenar datos (usando mapping_rules_json de la plantilla)
            $mappingRules = $template->mapping_rules_json; // JSON #1: Las reglas de mapeo de la plantilla

            if ($template->type === 'document') {
                $link = 'https://docs.google.com/document/d/';
                $docsService = $this->googleService->getDocsService();
                $requests = [];
                foreach ($mappingRules as $logicalKey => $placeholder) {
                    if (isset($dataForFilling[$logicalKey])) {
                        $requests[] = new GoogleDocsRequest([
                            'replaceAllText' => [
                                'replaceText' => (string) $dataForFilling[$logicalKey],
                                'containsText' => ['text' => $placeholder, 'matchCase' => true]
                            ]
                        ]);
                    }
                }
                if (!empty($requests)) {
                    $batchUpdateRequest = new BatchUpdateDocumentRequest(['requests' => $requests]);
                    $docsService->documents->batchUpdate($newGoogleDriveId, $batchUpdateRequest);
                }
            } elseif ($template->type === 'spreadsheets') {
                $link = 'https://docs.google.com/spreadsheets/d/';
                $sheetsService = $this->googleService->getSheetsService();
                foreach ($mappingRules as $logicalKey => $cellAddress) {
                    if (isset($dataForFilling[$logicalKey])) {
                        $value = $dataForFilling[$logicalKey];
                        // Asegúrate de que los valores para spreadsheets sean arrays de arrays
                        $valueForSheet = is_array($value) ? $value : [[(string) $value]];

                        $sheetsService->spreadsheets_values->update(
                            $newGoogleDriveId,
                            $cellAddress,
                            new ValueRange(['values' => $valueForSheet]),
                            ['valueInputOption' => 'RAW']
                        );
                    }
                }
            }

            // 3. Establecer Permisos (si visibilityStatus lo requiere)
            if ($visibilityStatus === 'public_editable') {
                $newPermission = new Permission();
                $newPermission->setType('anyone');
                $newPermission->setRole('writer');
                $driveService->permissions->create($newGoogleDriveId, $newPermission);
            } elseif ($visibilityStatus === 'public_viewable') {
                $newPermission = new Permission();
                $newPermission->setType('anyone');
                $newPermission->setRole('reader');
                $driveService->permissions->create($newGoogleDriveId, $newPermission);
            }

            // 4. Registrar en generated_documents (adaptar según tu modelo)
            $generatedDoc = GeneratedDocument::create([
                'google_drive_id' => $newGoogleDriveId,
                'user_id' => Auth::id(),
                'template_id' => $template->id,
                'title' => $newDocTitle,
                'type' => $template->type,
                'visibility_status' => $visibilityStatus,
                'generated_at' => now(),
                // Regla para hacer privado: ej. 3 horas después si era público
                'make_private_at' => ($visibilityStatus === 'public_editable' || $visibilityStatus === 'public_viewable') ? now()->addHours(3) : null,
                'data_values_json' => $dataForFilling, // <-- ¡Aquí se guarda el JSON de datos usados!
            ]);

            // 5. Log de Actividad
            activity()
                ->performedOn($generatedDoc)
                ->causedBy(Auth::user())
                ->event('document_generated')
                ->withProperties([
                    'template_name' => $template->name,
                    'document_link' => $link . $newGoogleDriveId . '/edit',
                    'visibility' => $visibilityStatus,
                ])
                ->log('generó un nuevo documento: "' . $newDocTitle . '".');

            return $link . $newGoogleDriveId . '/edit';

        } catch (\Google\Service\Exception $e) {
            $errorDetails = json_decode($e->getMessage(), true);
            $message = $errorDetails['error']['message'] ?? $e->getMessage();
            Log::error('Error de API de Google al generar documento: ' . $message, ['user_id' => Auth::id(), 'template_id' => $template->id]);
            throw new \Exception('Error al comunicarse con Google API: ' . $message);
        } catch (\Exception $e) {
            Log::error('Error inesperado al generar documento: ' . $e->getMessage(), ['user_id' => Auth::id(), 'template_id' => $template->id]);
            throw new \Exception('Error inesperado al generar el documento: ' . $e->getMessage());
        }
    }


    // --- Métodos de Generación para el Menú ---

    /**
     * Genera un documento en blanco a partir de una plantilla seleccionada.
     * @param Request $request Contiene 'template_id'.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateBlank(Request $request)
    {
        $request->validate([
            'template_id' => ['required', 'exists:templates,id'],
        ]);
        $template = Template::findOrFail($request->template_id); // Encuentra la plantilla por ID

        try {
            // Llama a la función helper de generación, sin datos específicos para prellenar
            $docLink = $this->generateDocument($template, [], 'private_restricted'); // O 'public_viewable' si quieres que sea visible
            return redirect()->route('documents.generated.success')->with(['docLink' => $docLink, 'docTitle' => $template->name]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Genera un documento usando datos predefinidos de un formato almacenado.
     * @param Request $request Contiene 'template_id' y opcionalmente 'predefined_format_id'.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generatePredefined(Request $request)
    {
        $request->validate([
            'template_id' => ['required', 'exists:templates,id'],
            'predefined_format_id' => ['nullable', 'exists:template_prefilled_data,id'], // Si eliges un ID específico
        ]);

        $template = Template::findOrFail($request->template_id);
        $predefinedFormat = null;

        if ($request->filled('predefined_format_id')) {
            $predefinedFormat = TemplatePrefilledData::findOrFail($request->predefined_format_id);
        } else {
            // Si no se especifica un ID de formato, busca la opción por defecto para esa plantilla
            $predefinedFormat = TemplatePrefilledData::where('template_id', $template->id)
                                                    ->where('is_default_option', true)
                                                    ->first();
            if (!$predefinedFormat) {
                return back()->with('error', 'No se encontró un formato predeterminado por defecto para esta plantilla.');
            }
        }

        // --- PREPARAR $dataForFilling combinando datos normalizados y JSON ---
        $dataForFilling = [];

        // 1. Datos genéricos (ej. del usuario autenticado)
        $dataForFilling['rpe_empleado'] = Auth::user()->rpe;
        $dataForFilling['nombre_empleado'] = Auth::user()->name;
        $dataForFilling['fecha_actual'] = now()->format('d/m/Y'); // Ejemplo de dato automático

        // 2. Datos de claves foráneas de TemplatePrefilledData
        // (Asumiendo que TemplatePrefilledData tiene FKs como tag_id, unidad_id y que tú los buscas aquí)
        if ($predefinedFormat->tag_id) { // Si tienes un campo tag_id en TemplatePrefilledData
             $tag = \App\Models\Tag::find($predefinedFormat->tag_id); // Asegúrate de importar el modelo Tag
             if ($tag) {
                 $dataForFilling['tag_instrumento'] = $tag->tag; // 'tag' es el nombre de la columna en tu tabla tags
             }
        }
        if ($predefinedFormat->unidad_id) { // Si tienes un campo unidad_id
            $unidad = \App\Models\Unidad::find($predefinedFormat->unidad_id); // Asegúrate de importar el modelo Unidad
            if ($unidad) {
                $dataForFilling['unidad_maquina'] = $unidad->name; // Asumiendo 'name' en tabla unidades
            }
        }
        // ... Repite para otros campos genéricos como sistema_id, servicio_id

        // 3. Datos únicos de $predefinedFormat->data_json (este es el JSON #2)
        if ($predefinedFormat->data_json) {
            $dataForFilling = array_merge($dataForFilling, $predefinedFormat->data_json);
        }
        // --- FIN DE LA PREPARACIÓN ---

        try {
            $docLink = $this->generateDocument($template, $dataForFilling, 'private_restricted');
            return redirect()->route('documents.generated.success')->with(['docLink' => $docLink, 'docTitle' => $template->name]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Muestra el formulario para que el usuario personalice los datos de una plantilla.
     * @param Template $template La instancia del modelo de plantilla.
     * @return \Illuminate\View\View
     */
    public function showCustomizeForm(Template $template) // Route Model Binding para Template
    {
        // Puedes pasar la plantilla a la vista para construir el formulario dinámicamente
        // o cargar un 'data_json' base de un predefined_format si el usuario parte de ahí
        return view('documents.customize_form', compact('template'));
    }

    /**
     * Genera un documento usando datos personalizados introducidos por el usuario.
     * @param Request $request Contiene 'template_id' y los datos del formulario.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateCustom(Request $request)
    {
        $request->validate([
            'template_id' => ['required', 'exists:templates,id'],
            // Agrega aquí reglas de validación para los campos que el usuario personaliza
            // ej. 'tag_instrumento' => ['required', 'string', 'max:255'],
            // 'rango_min_operativo' => ['nullable', 'numeric'],
        ]);

        $template = Template::findOrFail($request->template_id);

        // --- PREPARAR $dataForFilling con los datos del formulario y genéricos ---
        $dataForFilling = $request->except(['_token', 'template_id']); // Datos directamente del formulario

        // Añade aquí datos genéricos que no vienen del formulario pero sí de la DB/Usuario
        $dataForFilling['rpe_empleado'] = Auth::user()->rpe;
        $dataForFilling['nombre_empleado'] = Auth::user()->name;
        $dataForFilling['fecha_actual'] = now()->format('d/m/Y');
        // ... otros datos que necesites y que no estén en el formulario pero sí en la plantilla ...
        // Por ejemplo, si el formulario solo da el ID de un tag, aquí buscarías el nombre
        // $tag = \App\Models\Tag::find($request->input('tag_id'));
        // if ($tag) $dataForFilling['tag_instrumento'] = $tag->tag;
        // --- FIN DE LA PREPARACIÓN ---

        try {
            $docLink = $this->generateDocument($template, $dataForFilling, 'private_restricted'); // O 'public_editable'
            return redirect()->route('documents.generated.success')->with(['docLink' => $docLink, 'docTitle' => $template->name]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Muestra la vista de éxito después de generar un documento.
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showGeneratedSuccess(Request $request)
    {
        // Esta vista se muestra después de una redirección con datos de sesión flash
        $docLink = $request->session()->get('docLink');
        $docTitle = $request->session()->get('docTitle');

        if (!$docLink) {
            return redirect()->route('templates.index')->with('error', 'No se encontró un documento generado recientemente.');
        }

        return view('documents.generated_success', compact('docLink', 'docTitle'));
    }
}