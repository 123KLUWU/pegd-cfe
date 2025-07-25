<?php

namespace App\Http\Controllers;

use App\Models\Template; // ¡Importa tu modelo Template!
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Opcional, si lo necesitas para algo en este controlador
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleDriveService;

class TemplateController extends Controller
{
    protected $googleService; // Necesario para el helper de miniatura

    public function __construct(GoogleDriveService $googleService) // Inyectar GoogleDriveService
    {
        $this->googleService = $googleService; // Asignar al constructor
        $this->middleware('auth');
        $this->middleware('permission:generate documents');
    }

    public function index(Request $request)
    {
        $query = Template::where('is_active', true);

        // --- Lógica de Filtros y Búsqueda ---
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
        $filterCategoryId = $request->input('category_id');
        if ($filterCategoryId) {
            $query->where('category_id', $filterCategoryId);
        }

        $templates = $query->orderBy('name')->paginate(9);
        $categories = Category::all();

        // --- AÑADIR LÓGICA PARA OBTENER THUMBNAILLINK PARA CADA PLANTILLA ---
        foreach ($templates as $template) {
            $template->thumbnail_link = $this->getTemplateThumbnailLink($template);
        }
        // --- FIN LÓGICA THUMBNAILLINK ---

        return view('templates.index', [
            'templates' => $templates,
            'search_query' => $search,
            'selected_type' => $filterType,
            'selected_category_id' => $filterCategoryId,
            'categories' => $categories,
        ]);
    }
            /**
         * Helper: Obtiene el thumbnailLink de una plantilla de Google Drive.
         * Reutilizado de Admin\TemplateController.
         * @param Template $template
         * @return string|null El URL de la miniatura o null si no está disponible/error.
         */
        protected function getTemplateThumbnailLink(Template $template): ?string
        {
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
}