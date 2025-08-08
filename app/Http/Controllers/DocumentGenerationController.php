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
use App\Models\Diagram; // Tu modelo Diagram
use Illuminate\Support\Facades\Storage; // Para servir archivos
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Para generar QR
use Barryvdh\DomPDF\Facade\Pdf; // ¡Importa la fachada de DomPDF!
use Illuminate\Support\Str;
use App\Models\Tag;
use App\Models\Unidad;
// Importaciones de Google Docs
use Google\Service\Docs\BatchUpdateDocumentRequest;
use Google\Service\Docs\Request as GoogleDocsRequest;
use Google\Service\Exception as GoogleServiceException;

class DocumentGenerationController extends Controller
{
    protected $googleService;

    public function __construct(GoogleDriveService $googleService)
    {
        $this->googleService = $googleService;
        $this->middleware('auth');
        $this->middleware('permission:generate documents');
    }

    /**
     * Helper para la lógica común de generación de documentos (copiar, prellenar, registrar).
     * Es llamado por generateBlank, generatePredefined y generateCustom.
     *
     * @param Template $template La instancia del modelo de plantilla.
     * @param array $dataForFilling Los datos asociativos a usar para prellenar (clave => valor).
     * @param string $visibilityStatus El estado de visibilidad deseado ('public_editable', 'public_viewable', 'private_restricted').
     * @return string La URL del documento generado.
     * @throws Exception Si ocurre un error durante la generación.
     */
    protected function generateDocument(Template $template, array $dataForFilling, string $visibilityStatus = 'public_editable', ?int $instrumentoTagId = null, ?int $unidadId = null, ?int $prefilledDataId = null): string
    {
        try {
            $docsService = $this->googleService->getDocsService();
            $sheetsService = $this->googleService->getSheetsService();
            $driveService = $this->googleService->getDriveService();

            $newDocTitle = $template->name . ' - ' . Auth::user()->rpe . ' - ' . now()->format('YmdHis');

            // 1. Copiar la plantilla
            $copy = new \Google\Service\Drive\DriveFile([
                'name' => $newDocTitle,
                'parents' => [env('GOOGLE_GENERATED_DOCS_FOLDER_ID')], // <-- ¡CAMBIO AQUÍ!
            ]);
            $copiedFile = $driveService->files->copy($template->google_drive_id, $copy);
            $newGoogleDriveId = $copiedFile->getId();

            // 2. Prellenar datos (usando mapping_rules_json de la plantilla)
            $mappingRules = $template->mapping_rules_json; // JSON #1: Las reglas de mapeo de la plantilla

            if ($template->type === 'document') {
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
            $newPermission = new Permission();
            $newPermission->setType('anyone');
            $newPermission->setRole('writer'); // Siempre editable
            $driveService->permissions->create($newGoogleDriveId, $newPermission);
            $visibilityStatus = 'public_editable';

            // 4. Registrar en generated_documents (adaptar según tu modelo)
            $generatedDoc = GeneratedDocument::create([
                'google_drive_id' => $newGoogleDriveId,
                'user_id' => Auth::id(),
                'template_id' => $template->id,
                'instrumento_tag_id' => $instrumentoTagId,
                'unidad_id' => $unidadId,
                'prefilled_data_id' => $prefilledDataId,
                'title' => $newDocTitle,
                'type' => $template->type,
                'visibility_status' => $visibilityStatus,
                'generated_at' => now(),
                // Regla para hacer privado: ej. 3 o 4 horas después si era público
                'make_private_at' => now()->addHours(4),
                'data_values_json' => $dataForFilling,
            ]);

            if ($instrumentoTagId) {
                $instrumento = Tag::find($instrumentoTagId); // Buscar el instrumento por su ID
                if ($instrumento) {
                    $instrumento->last_calibration_date = now();
                    $instrumento->save();
                }
            }
            // 5. Log de Actividad
            activity()
                ->performedOn($generatedDoc)
                ->causedBy(Auth::user())
                ->event('document_generated')
                ->withProperties([
                    'template_name' => $template->name,
                    'document_link' => 'https://docs.google.com/' . $template->type . '/d/' . $newGoogleDriveId . '/edit',
                    'visibility' => $visibilityStatus,
                ])
                ->log('generó un nuevo documento: "' . $newDocTitle . '".');

                return 'https://docs.google.com/' . $template->type . '/d/' . $newGoogleDriveId . '/edit';

        } catch (GoogleServiceException $e) { // <-- ¡CAMBIO AQUÍ: Usar el alias de importación!
            // Captura y loggea la excepción específica de Google Service
            $errorDetails = json_decode($e->getMessage(), true); // Intenta decodificar el mensaje como JSON
            $loggedMessage = 'Error de Google API: ' . ($errorDetails['error']['message'] ?? $e->getMessage());
            $specificErrors = $errorDetails['error']['errors'] ?? []; // Errores más específicos si existen

            Log::error($loggedMessage, [
                'user_id' => Auth::id(),
                'template_id' => $template->id,
                'google_error_code' => $e->getCode(),
                'google_error_status' => $errorDetails['error']['status'] ?? 'N/A',
                'google_api_errors' => $specificErrors,
                'exception_trace' => $e->getTraceAsString(), // Trazado completo de la excepción
            ]);

            // Lanza una excepción con un mensaje más amigable para el usuario
            throw new Exception('Error al comunicarse con Google API: ' . ($errorDetails['error']['message'] ?? 'Detalles en logs.'));
        } catch (Exception $e) {
            // Catch-all para otras excepciones inesperadas
            Log::error('Error inesperado al generar documento: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'template_id' => $template->id,
                'exception_trace' => $e->getTraceAsString(),
            ]);
            throw new Exception('Error inesperado al generar el documento: ' . $e->getMessage());
        }
    }


    // --- Métodos de Generación para el Menú ---
    public function generatePredefined(Request $request)
    {
        $request->validate([
            'prefilled_data_id' => ['required', 'exists:template_prefilled_data,id'],
            'unidad_id' => ['required', 'exists:unidades,id'], // Added validation for unidad_id
        ]);

        $prefilledData = TemplatePrefilledData::findOrFail($request->prefilled_data_id);
        $template = $prefilledData->template; // Obtiene la plantilla asociada al formato prellenado
        $unidadId = $request->input('unidad_id'); // Get unidad_id from request
        $instrumentoTagId = $prefilledData->tag_id;
        if (!$template || !$template->is_active || $template->trashed()) {
            return back()->with('error', 'La plantilla asociada a este formato prellenado no es válida o no está activa.');
        }

        // --- PREPARACIÓN DE $dataForFilling ---
        $dataForFilling = [];

        // 1. Datos genéricos automáticos (ej. del usuario autenticado o fecha actual)
        $dataForFilling['rpe_empleado'] = Auth::user()->rpe;
        $dataForFilling['nombre_empleado'] = Auth::user()->name;
        $dataForFilling['fecha_actual'] = now()->format('d/m/Y');

        // 2. Datos de claves foráneas de TemplatePrefilledData (si los tienes en esa tabla)
        // Asume que TemplatePrefilledData tiene FKs como tag_id, unidad_id y que tú los buscas aquí.
        // Asegúrate de que los modelos Tag, Unidad, etc., estén importados o se resuelvan.
        if ($prefilledData->tag_id) {
             $tag = Tag::find($prefilledData->tag_id);
             if ($tag) {
                 $dataForFilling['tag_instrumento'] = $tag->tag;
             }
        }
        // Remove this section as unidad_id now comes from the request
        // if ($prefilledData->unidad_id) {
        //     $unidad = Unidad::find($prefilledData->unidad_id);
        //     if ($unidad) {
        //         $dataForFilling['unidad_maquina'] = $unidad->name;
        //     }
        // }
        
        // 3. Datos únicos del JSON 'data_json' (este es el JSON #2)
        if ($prefilledData->data_json) {
            $dataForFilling = array_merge($dataForFilling, $prefilledData->data_json);
        }
        // Add unidad_nombre to dataForFilling using the unidadId from the request
        if ($unidadId) {
            $unidad = Unidad::find($unidadId);
            if ($unidad) {
                $dataForFilling['unidad_nombre'] = $unidad->name;
            }
        }
        // --- FIN DE LA PREPARACIÓN ---

        try {
            $docLink = $this->generateDocument($template, $dataForFilling, 'public_editable', $instrumentoTagId, $unidadId, $prefilledData->id); // Pass unidadId to generateDocument
            return redirect()->route('documents.generated.success')->with(['docLink' => $docLink, 'docTitle' => $prefilledData->name]);
        } catch (Exception $e) {
            return back()->with('error', 'Hubo un error al generar el documento. ' . $e->getMessage());
        }
    }
    
    /**
     * Genera un documento en blanco a partir de una plantilla seleccionada.
     * @param Request $request Contiene 'template_id'.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateBlank(Request $request)
    {
        $request->validate([
            'template_id' => ['required', 'exists:templates,id'],
            'unidad_id' => ['required', 'exists:unidades,id'],
        ]);
        $template = Template::findOrFail($request->template_id); // Encuentra la plantilla por ID
        $unidadId = $request->input('unidad_id');

        $instrumentoTagId = null;
        $prefilledDataId = null;

        $dataForFilling = [];
        
        try {
            // Llama a la función helper de generación, sin datos específicos para prellenar
            $docLink = $this->generateDocument($template, [], 'public_editable', $instrumentoTagId, $unidadId); // Pass unidadId to generateDocument
            return redirect()->route('documents.generated.success')->with(['docLink' => $docLink, 'docTitle' => $template->name]);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function generateQrPdf(Template $template)
    {
        // URL a la que apuntará el QR (la ruta protegida para servir el archivo)
        $qrContentUrl = route('documents.customize.form', $template->id);
        // https://es.stackoverflow.com/questions/309482/integraci%c3%b3n-laravel-dompdf-y-qrcode-simplesoftwareio
        // Generar el código QR como SVG (es vectorial y de alta calidad para PDF)
        $qrSvg = QrCode::size(200)->format('svg')->generate($qrContentUrl);

        // Preparar los datos para la vista Blade del PDF
        $data = [
            'diagramName' => $template->name,
            'diagramDescription' => $template->description,
            'machineCategory' => "0",
            'qrCodeSvg' => $qrSvg,
            'qrContentUrl' => $qrContentUrl,
        ];
        $pdf = Pdf::loadView('qr.document_qr', $data);
        return $pdf->stream('qr_diagrama_' . Str::slug($template->name) . '.pdf');
    }
    /**
     * Muestra el formulario para que el usuario personalice los datos de una plantilla.
     * @param Template $template La instancia del modelo de plantilla.
     * @return \Illuminate\View\View
     */
    public function showCustomizeForm(Template $template) // Route Model Binding para Template
    {
        $instrumentos = Tag::all();
        $unidades = Unidad::all(); // <-- Obtener todas las unidades
        return view('documents.customize_form', compact('template', 'instrumentos', 'unidades'));
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
            'unidad_id' => ['required', 'exists:unidades,id'], // <-- AÑADE VALIDACIÓN
            'instrumento_tag_id' => ['nullable', 'exists:tags,id'], // <-- AÑADE VALIDACIÓN
            // ... (otras validaciones de campos personalizados) ...
        ]);

        $template = Template::findOrFail($request->template_id);
        $unidadId = $request->input('unidad_id');
        $instrumentoTagId = $request->input('instrumento_tag_id');
        $prefilledDataId = null; // No hay prellenado si es personalizado

        // --- PREPARACIÓN DE $dataForFilling con los datos del formulario y genéricos ---
        $dataForFilling = $request->except(['_token', 'template_id', 'unidad_id', 'instrumento_tag_id']);

        $dataForFilling['rpe_empleado'] = Auth::user()->rpe;
        $dataForFilling['nombre_empleado'] = Auth::user()->name;
        $dataForFilling['fecha_actual'] = now()->format('d/m/Y');

        if ($unidadId) {
            $unidad = Unidad::find($unidadId);
            if ($unidad) {
                $dataForFilling['unidad_nombre'] = $unidad->name; // Nombre de la unidad
            }
        }
        if ($instrumentoTagId) {
            $instrumento = Tag::find($instrumentoTagId);
            if ($instrumento) {
                $dataForFilling['instrumento_tag'] = $instrumento->tag;
                $dataForFilling['instrumento_modelo'] = $instrumento->model ?? 'N/A';
                $dataForFilling['instrumento_serial'] = $instrumento->serial_number ?? 'N/A';
                $dataForFilling['instrumento_descripcion'] = $instrumento->description ?? 'N/A';
            }
        }

        try {
            $docLink = $this->generateDocument($template, $dataForFilling, 'public_editable', $instrumentoTagId, $unidadId, $prefilledDataId);
            return response()->json(['success' => true, 'docLink' => $docLink, 'docTitle' => $template->name]);
        } catch (Exception $e) {
            Log::error('Error al generar documento personalizado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Hubo un error al generar el documento. ' . $e->getMessage()], 500);
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

        return redirect()->away($docLink);
    }

    public function generatePredefinedByQr(Request $request)
    {
        $request->validate([
            'prefilled_data_id' => ['required', 'exists:template_prefilled_data,id'],
        ]);

        $prefilledData = TemplatePrefilledData::findOrFail($request->prefilled_data_id);
        $template = $prefilledData->template;

        if (!$template || !$template->is_active || $template->trashed()) {
            return redirect()->route('login')->with('error', 'La plantilla asociada a este formato prellenado no es válida o no está activa.');
        }

        // --- PREPARACIÓN DE $dataForFilling ---
        $dataForFilling = [];

        $dataForFilling['rpe_empleado'] = Auth::user()->rpe ?? 'QR_SCANNER';
        $dataForFilling['nombre_empleado'] = Auth::user()->name ?? 'Usuario QR';
        $dataForFilling['fecha_actual'] = now()->format('d/m/Y');

        // Aquí es donde JALAS los datos de las tablas maestras a través de las FKs del prellenado
        $instrumentoTagId = $prefilledData->tag_id;
        $unidadId = $prefilledData->unidad_id; // <-- OBTENER UNIDAD DEL PRELLENADO

        // Cargar los modelos para obtener los nombres
        if ($unidadId) {
            $unidad = Unidad::find($unidadId);
            if ($unidad) {
                $dataForFilling['unidad_nombre'] = $unidad->name;
            }
        }
        if ($instrumentoTagId) {
            $instrumento = Tag::find($instrumentoTagId);
            if ($instrumento) {
                $dataForFilling['instrumento_tag'] = $instrumento->tag;
                $dataForFilling['instrumento_modelo'] = $instrumento->model ?? 'N/A';
                $dataForFilling['instrumento_serial'] = $instrumento->serial_number ?? 'N/A';
                $dataForFilling['instrumento_descripcion'] = $instrumento->description ?? 'N/A';
            }
        }
        // ... repite para tags, sistemas, servicios ...

        // Mezcla los datos normalizados con el JSON del prellenado
        if ($prefilledData->data_json) {
            $dataForFilling = array_merge($dataForFilling, $prefilledData->data_json);
        }
        // --- FIN DE LA PREPARACIÓN ---

        try {
            $docLink = $this->generateDocument($template, $dataForFilling, 'public_editable', $instrumentoTagId, $unidadId, $prefilledData->id);
            return redirect()->away($docLink);
        } catch (Exception $e) {
            Log::error('Error al generar documento por QR: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Hubo un error al generar el documento por QR. Por favor, inicia sesión y vuelve a intentarlo.');
        }
    }
}