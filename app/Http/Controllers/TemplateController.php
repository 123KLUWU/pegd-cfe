<?php

namespace App\Http\Controllers;

use App\Models\Template; // ¡Importa tu modelo Template!
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Opcional, si lo necesitas para algo en este controlador
use App\Models\Category;

class TemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:generate documents'); // Asegura que los usuarios tengan permiso para ver plantillas
    }

    public function index(Request $request)
    {
        $query = Template::where('is_active', true); // Solo mostrar plantillas activas

        // --- Lógica de Filtros y Búsqueda (Similar al admin.diagrams.index) ---
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

        $templates = $query->orderBy('name')->paginate(9); // Paginación de 9 por página
        $categories = Category::all(); // Todas las categorías para el filtro

        return view('templates.index', [
            'templates' => $templates,
            'search_query' => $search,
            'selected_type' => $filterType,
            'selected_category_id' => $filterCategoryId,
            'categories' => $categories, // Pasar las categorías al filtro
        ]);
    }
}