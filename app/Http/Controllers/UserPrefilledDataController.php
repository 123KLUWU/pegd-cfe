<?php

namespace App\Http\Controllers;

use App\Models\Template; // Modelo de plantilla
use App\Models\TemplatePrefilledData; // Modelo de datos prellenados
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPrefilledDataController extends Controller
{
    /**
     * Constructor. Protege las rutas para usuarios.
     */
    public function __construct()
    {
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

        // Solo mostrar formatos asociados a plantillas activas y que no estén soft-deleted
        $query->whereHas('template', function ($q) {
            $q->where('is_active', true)->whereNull('deleted_at');
        })->whereNull('deleted_at'); // Solo formatos prellenados no soft-deleted

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

        $prefilledData = $query->orderBy('name')->paginate(12); // Paginación de 12 por página

        // Obtener todas las plantillas activas para el filtro
        $templates = Template::where('is_active', true)->get();

        return view('prefilled_data.index', [
            'prefilledData' => $prefilledData,
            'search_query' => $search,
            'selected_template_id' => $filterTemplateId,
            'available_templates' => $templates,
        ]);
    }
}