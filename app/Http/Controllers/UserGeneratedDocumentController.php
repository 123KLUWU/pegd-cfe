<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeneratedDocument; // Modelo de documento generado
use App\Models\Template; // Para filtros
use App\Services\GoogleDriveService; // Para miniaturas
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Tag;
use App\Models\Unidad; 
use App\Models\Supervisor;

class UserGeneratedDocumentController extends Controller
{
    protected $googleService;

    public function __construct(GoogleDriveService $googleService)
    {
        $this->googleService = $googleService;
        $this->middleware('auth');
        // Permiso para que los usuarios puedan ver sus propios documentos generados
        $this->middleware('permission:generate documents');
    }
     /**
     * Muestra una lista paginada de los documentos generados por el usuario autenticado.
     * Incluye opciones de búsqueda y filtro.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = GeneratedDocument::where('user_id', Auth::id())->with('template'); // Solo documentos del usuario actual
        $supervisors = Supervisor::orderBy('name')->get(['name','email']);

        // --- Filtros ---
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('google_drive_id', 'like', '%' . $search . '%')
                  ->orWhereHas('template', function ($q_template) use ($search) {
                      $q_template->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $filterType = $request->input('type');
        if ($filterType && in_array($filterType, ['document', 'spreadsheets'])) {
            $query->where('type', $filterType);
        }

        $filterVisibility = $request->input('visibility');
        if ($filterVisibility && in_array($filterVisibility, ['public_editable', 'public_viewable', 'private_restricted'])) {
            $query->where('visibility_status', $filterVisibility);
        }

        $filterStatus = $request->input('status'); // 'trashed', 'with_trashed'
        if ($filterStatus) {
            if ($filterStatus === 'trashed') $query->onlyTrashed();
            elseif ($filterStatus === 'with_trashed') $query->withTrashed();
        } else {
            $query->whereNull('deleted_at'); // Por defecto, solo no soft-deleted
        }

        $documents = $query->orderBy('generated_at', 'desc')->paginate(9); // Paginación de 9 por página

        // --- OBTENER MINIATURAS (thumbnailLink) ---
        foreach ($documents as $document) {
            $document->thumbnail_link = $this->getDocumentThumbnailLink($document);
        }

        // Datos para filtros de la vista
        $templates = Template::whereIn('id', $documents->pluck('template_id')->filter()->unique())->get(); // Solo plantillas usadas por el usuario

        return view('user_documents.index', [
            'documents' => $documents,
            'search_query' => $search,
            'selected_type' => $filterType,
            'selected_visibility' => $filterVisibility,
            'selected_status' => $filterStatus,
            'available_templates' => $templates,
            'supervisors'    => $supervisors,
        ]);
    }
    
    /**
     * Muestra la página de detalles de un documento generado por el usuario.
     * @param GeneratedDocument $generatedDocument
     * @return \Illuminate\View\View
     */
    public function show(GeneratedDocument $generatedDocument)
    {
        // Asegurarse de que el documento pertenece al usuario autenticado
        if ($generatedDocument->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Acceso no autorizado a este documento.');
        }
        // Si el documento está soft-deleted, solo el admin o el propio usuario puede verlo
        if ($generatedDocument->trashed() && $generatedDocument->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(404);
        }

        // Obtener la miniatura para la vista de detalle
        $generatedDocument->thumbnail_link = $this->getDocumentThumbnailLink($generatedDocument);

        // Historial de actividad de este documento (si lo quieres específico)
        $documentActivities = Activity::where('subject_type', GeneratedDocument::class)
                                      ->where('subject_id', $generatedDocument->id)
                                      ->latest()
                                      ->paginate(5, ['*'], 'activities_page'); // Paginación más pequeña

        return view('user_documents.show', compact('generatedDocument', 'documentActivities'));
    }
     /**
     * Elimina lógicamente un documento generado por el usuario.
     * @param GeneratedDocument $generatedDocument
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(GeneratedDocument $generatedDocument)
    {
        // Asegurarse de que el documento pertenece al usuario autenticado
        if ($generatedDocument->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para eliminar este documento.');
        }

        DB::transaction(function () use ($generatedDocument) {
            $generatedDocument->delete(); // Soft delete
            activity()
                ->performedOn($generatedDocument)
                ->causedBy(Auth::user())
                ->event('generated_document_soft_deleted')
                ->log('eliminó (soft delete) su documento: "' . $generatedDocument->title . '".');
        });

        return redirect()->route('user.generated-documents.index')->with('success', 'Documento eliminado suavemente.');
    }
        /**
     * Restaura un documento generado soft-deleted por el usuario.
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $generatedDocument = GeneratedDocument::onlyTrashed()->findOrFail($id);

        // Asegurarse de que el documento pertenece al usuario autenticado
        if ($generatedDocument->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para restaurar este documento.');
        }

        DB::transaction(function () use ($generatedDocument) {
            $generatedDocument->restore();
            activity()
                ->performedOn($generatedDocument)
                ->causedBy(Auth::user())
                ->event('generated_document_restored')
                ->log('restauró su documento: "' . $generatedDocument->title . '".');
        });

        return redirect()->route('user.generated-documents.index')->with('success', 'Documento restaurado exitosamente.');
    }

     /**
     * Muestra los documentos generados por el usuario, agrupados por Unidad e Instrumento.
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function indexByUnitAndInstrument(Request $request)
    {
        $documents = GeneratedDocument::query() // REMOVIDO: ->where('user_id', Auth::id())
        ->whereNull('deleted_at') // Solo documentos no soft-deleted
        ->with(['unidad', 'instrumento', 'template']) // Cargar relaciones
        ->orderBy('generated_at', 'desc')
        ->get(); // Obtener todos los documentos para agrupar en memoria

        // 2. Obtener miniaturas para cada documento
        foreach ($documents as $document) {
            $document->thumbnail_link = $this->getDocumentThumbnailLink($document);
        }

        $groupedDocuments = $documents->groupBy('unidad.unidad')->map(function ($documentsByUnit) {
            return $documentsByUnit->groupBy('instrumento.tag');
        });

        $availableUnidades = Unidad::whereHas('generatedDocuments', function($q) {
            $q->whereNull('deleted_at'); // Solo unidades con documentos no soft-deleted
        })->orderBy('unidad')->get();

        $availableInstrumentos = Tag::whereHas('generatedDocuments', function($q) {
            $q->whereNull('deleted_at'); // Solo instrumentos con documentos no soft-deleted
        })->orderBy('tag')->get();

        return view('user_documents.index_by_unit_instrument', compact('groupedDocuments', 'availableUnidades', 'availableInstrumentos'));
    }
    
     /**
     * Nivel 1: Muestra un listado de todas las unidades con documentos generados.
     * @return \Illuminate\View\View
     */
    public function indexByUnit()
    {
        // Obtener todas las unidades que tienen al menos un documento generado por cualquier usuario
        $unidades = Unidad::whereHas('generatedDocuments', function ($query) {
            $query->whereNull('deleted_at'); // Solo unidades con documentos activos
        })->withCount(['generatedDocuments' => function ($query) {
            $query->whereNull('deleted_at'); // Contar solo documentos activos
        }])->get();

        return view('user_documents.unit_list', compact('unidades'));
    }

    /**
     * Nivel 2: Muestra los instrumentos con documentos generados para una unidad específica.
     * @param Unidad $unidad
     * @return \Illuminate\View\View
     */
    public function showInstrumentsByUnit(Unidad $unidad)
    {
        // Obtener todos los instrumentos que tienen documentos generados en esta unidad
        $instrumentos = Tag::whereHas('generatedDocuments', function ($query) use ($unidad) {
            $query->where('unidad_id', $unidad->id)->whereNull('deleted_at');
        })->withCount(['generatedDocuments' => function ($query) use ($unidad) {
            $query->where('unidad_id', $unidad->id)->whereNull('deleted_at');
        }])->get();

        return view('user_documents.instrument_list', compact('unidad', 'instrumentos'));
    }

    /**
     * Nivel 3: Muestra los documentos para una unidad e instrumento específicos.
     * @param Unidad $unidad
     * @param Tag $instrumento
     * @return \Illuminate\View\View
     */
    public function showDocumentsByUnitAndInstrument(Unidad $unidad, Tag $instrumento)
    {
        $documents = GeneratedDocument::where('unidad_id', $unidad->id)
            ->where('instrumento_tag_id', $instrumento->id)
            ->whereNull('deleted_at')
            ->with(['template', 'user'])
            ->orderBy('generated_at', 'desc')
            ->get();

        foreach ($documents as $document) {
            $document->thumbnail_link = $this->getDocumentThumbnailLink($document);
        }

        return view('user_documents.document_list', compact('unidad', 'instrumento', 'documents'));
    }

    /**
     * Helper: Obtiene el thumbnailLink de un documento de Google Drive.
     * Reutilizado de Admin\GeneratedDocumentController.
     * @param GeneratedDocument $document
     * @return string|null El URL de la miniatura o null si no está disponible/error.
     */
    protected function getDocumentThumbnailLink(GeneratedDocument $document): ?string
    {
        try {
            $driveService = $this->googleService->getDriveService();
            $file = $driveService->files->get($document->google_drive_id, ['fields' => 'thumbnailLink']);
            return $file->getThumbnailLink();
        } catch (\Google\Service\Exception $e) {
            Log::warning("No se pudo obtener miniatura para documento GDrive ID: {$document->google_drive_id}. Error: {$e->getMessage()}");
            return null;
        } catch (\Exception $e) {
            Log::error("Error inesperado al obtener miniatura para documento GDrive ID: {$document->google_drive_id}: {$e->getMessage()}");
            return null;
        }
    }

}
