<?php

namespace App\Http\Controllers;

use App\Models\Template; // Modelo de plantilla
use App\Models\TemplatePrefilledData; // Modelo de datos prellenados
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Para el helper de miniatura
use App\Services\GoogleDriveService;
use SimpleSoftwareIO\QrCode\Facades\QrCode; 
use Barryvdh\DomPDF\Facade\Pdf; // ¡Importa la fachada de DomPDF!
use Illuminate\Support\Str;
use App\Models\Tag;
use App\Models\Unidad;
use App\Models\EquipoPatron;

class UserPrefilledDataController extends Controller
{
    protected $googleService; // Necesario para el helper de miniatura

    public function __construct(GoogleDriveService $googleService) // Inyectar GoogleDriveService
    {
        $this->googleService = $googleService; // Asignar al constructor
        $this->middleware('auth');
        // Opcional: Asegura que los usuarios tengan permiso para ver y usar formatos prellenados
        // $this->middleware('permission:use prefilled data');
    }

    /**
     * Muestra el listado de formatos prellenados para los usuarios, con búsqueda y filtros.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = TemplatePrefilledData::query();

        // Cargar la relación 'template' para acceder a sus propiedades
        // y solo mostrar formatos asociados a plantillas activas y no eliminadas
        $query->whereHas('template', function ($q) {
            $q->where('is_active', true)->whereNull('deleted_at');
        })->whereNull('deleted_at');

        // --- Lógica de Búsqueda ---
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // --- Lógica de Filtro por Plantilla ---
        $filterTemplateId = $request->input('template_id');
        if ($filterTemplateId) {
            $query->where('template_id', $filterTemplateId);
        }

        $prefilledData = $query->with('template')->orderBy('name')->paginate(12); // Cargar la relación 'template'

        // --- OBTENER THUMBNAILLINK PARA CADA PLANTILLA ASOCIADA ---
        // Esto se hace después de la paginación para no sobrecargar la API de Google
        foreach ($prefilledData as $data) {
            if ($data->template) { // Asegurarse de que la plantilla existe
                $data->template->thumbnail_link = $this->getTemplateThumbnailLink($data->template);
            }
        }
        // --- FIN OBTENCIÓN THUMBNAILLINK ---

        $templates = Template::where('is_active', true)->get(); // Para el filtro de plantillas

        return view('prefilled_data.index', [
            'prefilledData' => $prefilledData,
            'search_query' => $search,
            'selected_template_id' => $filterTemplateId,
            'available_templates' => $templates,
        ]);
    }
        /**
     * Helper: Obtiene el thumbnailLink de una plantilla de Google Drive.
     * Duplicado de Admin\TemplateController para este contexto.
     * @param Template $template
     * @return string|null El URL de la miniatura o null si no está disponible/error.
     */
    protected function getTemplateThumbnailLink(Template $template): ?string
    {
        //https://developers.google.com/workspace/drive/api/guides/file?hl=es-419#upload-thumbnails
        try {
            $driveService = $this->googleService->getDriveService();
            $file = $driveService->files->get($template->google_drive_id, ['fields' => 'thumbnailLink']);
            return $file->getThumbnailLink();
        } catch (\Google\Service\Exception $e) {
            Log::warning("No se pudo obtener miniatura para plantilla ID: {$template->id}. Error: {$e->getMessage()}");
            return null;
        } catch (\Exception $e) {
            Log::error("Error inesperado al obtener miniatura para plantilla ID: {$template->id}: {$e->getMessage()}");
            return null;
        }
    }
    
    public function generateQrPdf(TemplatePrefilledData $TemplatePrefilledData)
    {
        // dd($TemplatePrefilledData);
        // URL a la que apuntará el QR (la ruta protegida para servir el archivo)
        $qrContentUrl = route('prefilled-data.generate_confirmation_form', $TemplatePrefilledData->id);
        // https://es.stackoverflow.com/questions/309482/integraci%c3%b3n-laravel-dompdf-y-qrcode-simplesoftwareio
        // Generar el código QR como SVG (es vectorial y de alta calidad para PDF)
        $qrSvg = QrCode::size(200)->format('svg')->generate($qrContentUrl); 

        // Preparar los datos para la vista Blade del PDF
        $data = [
            'diagramName' => $TemplatePrefilledData->name,
            'diagramDescription' => $TemplatePrefilledData->description,
            'machineCategory' => "0",
            'qrCodeSvg' => $qrSvg,
            'qrContentUrl' => $qrContentUrl,
        ];
        $pdf = Pdf::loadView('qr.document_qr', $data);
        return $pdf->stream('qr_diagrama_' . Str::slug($TemplatePrefilledData->name) . '.pdf');
    }

    /**
     * Muestra la página de confirmación antes de generar el documento predefinido.
     * Aquí el usuario puede seleccionar el instrumento y confirmar la generación.
     *
     * @param TemplatePrefilledData $prefilledData
     * @return \Illuminate\View\View
     */
    public function showGenerateConfirmationForm(TemplatePrefilledData $prefilledData)
    {
        if ($prefilledData->trashed() || !$prefilledData->template || !$prefilledData->template->is_active || $prefilledData->template->trashed()) {
            abort(404, 'Formato prellenado o plantilla asociada no válida.');
        }

        $unidades = Unidad::all(); // Cargar todos los instrumentos para el selector
        $instrumentos = Tag::all(); // Cargar todos los instrumentos para el selector
        $equiposPatrones = EquipoPatron::all();
        return view('prefilled_data.generate_confirmation', compact('prefilledData', 'instrumentos', 'unidades', 'equiposPatrones'));
    }
}