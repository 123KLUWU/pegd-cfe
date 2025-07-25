<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneratedDocument; // Tu modelo de documento generado
use App\Models\User; // Para filtros y relaciones
use App\Models\Template; // Para filtros y relaciones
use App\Services\GoogleDriveService; // Para interactuar con Google Drive API (miniaturas, permisos)
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity; // Para logs
use Google\Service\Drive\Permission;

class GeneratedDocumentController extends Controller
{
    protected $googleService;

    public function __construct(GoogleDriveService $googleService)
    {
        $this->googleService = $googleService;
        $this->middleware('auth');
        // Un permiso específico para administrar documentos generados, o el rol 'admin'
        $this->middleware('role:admin|permission:view all documents');
    }

    /**
     * Muestra una lista paginada de todos los documentos generados, con filtros y búsqueda.
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = GeneratedDocument::query()->with(['user', 'template']); // Cargar relaciones para mostrar nombres

        // --- Filtros ---
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('google_drive_id', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($q_user) use ($search) {
                      $q_user->where('name', 'like', '%' . $search . '%')->orWhere('rpe', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('template', function ($q_template) use ($search) {
                      $q_template->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $filterType = $request->input('type');
        if ($filterType && in_array($filterType, ['docs', 'sheets'])) { // Asumiendo 'docs' y 'sheets' como tipos
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

        $documents = $query->orderBy('generated_at', 'desc')->paginate(15);

        // --- OBTENER MINIATURAS (thumbnailLink) ---
        foreach ($documents as $document) {
            $document->thumbnail_link = $this->getDocumentThumbnailLink($document);
        }

        // Datos para filtros de la vista
        $users = User::all(); // Todos los usuarios para el filtro 'Generado por'
        $templates = Template::all(); // Todas las plantillas para el filtro 'Plantilla'

        return view('admin.generated_documents.index', [
            'documents' => $documents,
            'search_query' => $search,
            'selected_type' => $filterType,
            'selected_visibility' => $filterVisibility,
            'selected_status' => $filterStatus,
            'available_users' => $users, // Pasar usuarios
            'available_templates' => $templates, // Pasar plantillas
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(GeneratedDocument $generatedDocument)
    {
        // Obtener la miniatura para la vista de detalle
        $generatedDocument->thumbnail_link = $this->getDocumentThumbnailLink($generatedDocument);

        // Historial de actividad de este documento (si lo quieres específico)
        $documentActivities = Activity::where('subject_type', GeneratedDocument::class)
                                    ->where('subject_id', $generatedDocument->id)
                                    ->latest()
                                    ->paginate(10, ['*'], 'activities_page');

        return view('admin.generated_documents.show', compact('generatedDocument', 'documentActivities'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GeneratedDocument $generatedDocument)
    {
        DB::transaction(function () use ($generatedDocument) {
            $generatedDocument->delete(); // Soft delete
            activity()
                ->performedOn($generatedDocument)
                ->causedBy(Auth::user())
                ->event('generated_document_soft_deleted')
                ->log('eliminó (soft delete) el documento: "' . $generatedDocument->title . '".');
        });

        return redirect()->route('generated-documents.index')->with('success', 'Documento eliminado suavemente.');
    }

    /**
     * Cambia la visibilidad de un documento generado en Google Drive y actualiza el registro.
     * @param Request $request
     * @param GeneratedDocument $generatedDocument
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeVisibility(Request $request, GeneratedDocument $generatedDocument)
    {
        $request->validate([
            'visibility_status' => ['required', 'in:public_editable,public_viewable,private_restricted'],
        ]);

        $newStatus = $request->visibility_status;

        try {
            $driveService = $this->googleService->getDriveService();

            // 1. Eliminar permisos existentes de 'anyone' para empezar de cero
            $permissionsList = $driveService->permissions->listDocuments($generatedDocument->google_drive_id, ['fields' => 'permissions(id,type)']);
            foreach ($permissionsList->getPermissions() as $permission) {
                if ($permission->getType() === 'anyone') {
                    $driveService->permissions->delete($generatedDocument->google_drive_id, $permission->getId());
                }
            }

            // 2. Añadir el nuevo permiso según el estado deseado
            if ($newStatus === 'public_editable') {
                $permission = new Permission(['type' => 'anyone', 'role' => 'writer']);
                $driveService->permissions->create($generatedDocument->google_drive_id, $permission);
            } elseif ($newStatus === 'public_viewable') {
                $permission = new Permission(['type' => 'anyone', 'role' => 'reader']);
                $driveService->permissions->create($generatedDocument->google_drive_id, $permission);
            }
            // Si es 'private_restricted', no se añade permiso 'anyone'

            // 3. Actualizar el registro en la DB
            $generatedDocument->visibility_status = $newStatus;
            // Si se hace privado, nullify make_private_at
            $generatedDocument->make_private_at = ($newStatus === 'private_restricted') ? null : now()->addHours(3); // Re-establecer para públicos

            $generatedDocument->save();

            // Registrar actividad
            activity()
                ->performedOn($generatedDocument)
                ->causedBy(Auth::user())
                ->event('document_visibility_changed')
                ->log('cambió la visibilidad del documento "' . $generatedDocument->title . '" a: ' . $newStatus);

            return back()->with('success', 'Visibilidad del documento actualizada a ' . $newStatus . '.');

        } catch (\Google\Service\Exception $e) {
            $errorDetails = json_decode($e->getMessage(), true);
            $message = $errorDetails['error']['message'] ?? $e->getMessage();
            Log::error("Error al cambiar visibilidad de documento {$generatedDocument->id}: {$message}");
            return back()->with('error', 'Error al cambiar visibilidad: ' . $message);
        }
    }

    /**
     * Restaura un documento generado soft-deleted.
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore($id)
    {
        $generatedDocument = GeneratedDocument::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($generatedDocument) {
            $generatedDocument->restore();
            activity()
                ->performedOn($generatedDocument)
                ->causedBy(Auth::user())
                ->event('generated_document_restored')
                ->log('restauró el documento: "' . $generatedDocument->title . '".');
        });

        return redirect()->route('generated-documents.index')->with('success', 'Documento restaurado exitosamente.');
    }
     /**
     * Elimina permanentemente un documento generado y, opcionalmente, de Google Drive.
     * ¡CUIDADO! Romperá referencias.
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forceDelete($id)
    {
        $generatedDocument = GeneratedDocument::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($generatedDocument) {
            try {
                // Opcional: Eliminar el archivo también de Google Drive
                $driveService = $this->googleService->getDriveService();
                $driveService->files->delete($generatedDocument->google_drive_id);
                Log::info("Archivo {$generatedDocument->google_drive_id} eliminado de Google Drive.");
            } catch (\Google\Service\Exception $e) {
                // Si el archivo no existe en Drive o ya fue borrado, loggear pero no detener
                Log::warning("No se pudo eliminar el archivo {$generatedDocument->google_drive_id} de Google Drive. Puede que ya no exista. Error: {$e->getMessage()}");
            } catch (\Exception $e) {
                 Log::error("Error inesperado al intentar borrar de Drive {$generatedDocument->google_drive_id}: {$e->getMessage()}");
            }

            $generatedDocument->forceDelete(); // Eliminar permanentemente de la DB

            activity()
                ->performedOn(null) // Sujeto nulo si el documento se borra permanentemente
                ->causedBy(Auth::user())
                ->event('generated_document_force_deleted')
                ->log('eliminó PERMANENTEMENTE el documento: "' . $generatedDocument->title . '".');
        });

        return redirect()->route('generated-documents.index')->with('success', 'Documento eliminado permanentemente.');
    }
     // --- Helper para obtener miniaturas (reutilizado) ---
     protected function getDocumentThumbnailLink(GeneratedDocument $document): ?string
     {
         try {
             $driveService = $this->googleService->getDriveService();
             // Para documentos de Google, pedimos el thumbnailLink
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
