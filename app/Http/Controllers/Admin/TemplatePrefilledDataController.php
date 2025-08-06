<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template; // Modelo de la plantilla
use App\Models\TemplatePrefilledData; // Tu modelo de datos prellenados
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Para transacciones
use App\Models\Tag; // Importa el modelo Tag (que ahora es Instrumento)
use App\Models\Unidad;

class TemplatePrefilledDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // El usuario debe tener el rol 'admin' O el permiso 'manage templates'
        $this->middleware('role:admin|permission:manage templates');
    }

    /**
     * Muestra el listado de todos los formatos prellenados (para administradores).
     * Este será el nuevo menú principal para prellenados.
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = TemplatePrefilledData::query();

        // --- Filtros ---
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
                // Opcional: buscar dentro del JSON (más complejo, requiere MySQL 5.7.8+ o MariaDB 10.2.3+)
                // $q->orWhereJsonContains('data_json', $search);
            });
        }
        $filterTemplateId = $request->input('template_id');
        if ($filterTemplateId) {
            $query->where('template_id', $filterTemplateId);
        }
        // Eliminado: $filterDefault = $request->input('is_default_option');
        // Eliminado: if ($filterDefault !== null) { ... }
        $filterStatus = $request->input('status'); // 'trashed', 'with_trashed'
        if ($filterStatus) {
            if ($filterStatus === 'trashed') $query->onlyTrashed();
            elseif ($filterStatus === 'with_trashed') $query->withTrashed();
        } else {
            $query->whereNull('deleted_at'); // Default: solo no eliminados
        }

        $prefilledData = $query->orderBy('name')->paginate(15);
        $templates = Template::all(); // Para el filtro de plantillas

        return view('admin.template_prefilled_data.index', [
            'prefilledData' => $prefilledData,
            'search_query' => $search,
            'selected_template_id' => $filterTemplateId,
            // Eliminado: 'selected_default_option' => $filterDefault,
            'selected_status' => $filterStatus,
            'templates' => $templates,
        ]);
    }

    // Muestra el formulario para crear un nuevo conjunto de datos prellenados
    // Se espera el ID de la plantilla a la que pertenece este formato
    public function create(Template $template) // Route Model Binding
    {
        // Opcional: Podrías pasar la plantilla a la vista
        $instrumentos = unidad::all(); // Pasar instrumentos (Tags) a la vista
        return view('admin.template_prefilled_data.create', compact('template', 'instrumentos'));
    }

    // Muestra el formulario para editar un conjunto de datos prellenados existente
    public function edit(TemplatePrefilledData $prefilledData) // Route Model Binding
    {
        // Accede a $prefilledData->data_json para mostrarlo en el formulario de edición
        $instrumentos = Tag::all(); // Pasar instrumentos (Tags) a la vista

        return view('admin.template_prefilled_data.edit', compact('prefilledData', 'templates', 'instrumentos'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'template_id' => ['required', 'exists:templates,id'],
            'instrumento_tag_id' => ['nullable', 'exists:tags,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Validar los arrays de claves y valores dinámicos
            'dynamic_keys.*' => ['nullable', 'string', 'max:255'], // Las claves pueden ser nulas para filas vacías
            'dynamic_values.*' => ['nullable', 'string'], // Los valores también pueden ser nulos
    ]);

    $dataJson = [];
    $keys = $request->input('dynamic_keys');
    $values = $request->input('dynamic_values');

    if ($keys && is_array($keys)) {
        foreach ($keys as $index => $key) {
            if (!empty($key)) { // Solo procesa si la clave no está vacía
                $dataJson[$key] = $values[$index] ?? null; // Asigna el valor
            }
        }
    }

    // Opcional: Si quieres asegurar que al menos un par clave-valor fue enviado
    if (empty($dataJson) && !$request->filled('description')) { // Si no hay datos Y la descripción también está vacía
        return back()->withInput()->withErrors(['dynamic_keys' => 'Debe añadir al menos un par clave-valor para los datos prellenados.']);
    }

    DB::transaction(function () use ($request, $dataJson) {
        TemplatePrefilledData::create([
            'template_id' => $request->template_id,
            'name' => $request->name,
            'description' => $request->description,
            'data_json' => $dataJson, // Laravel lo guardará como JSON automáticamente
            'is_default_option' => $request->has('is_default_option'),
            'created_by_user_id' => Auth::id(),
            'instrumento_tag_id' => $request->instrumento_tag_id, // <-- Guardar el ID del instrumento

            // ... otros FKs como tag_id, unidad_id si los manejas aquí directamente
        ]);
    });

    return redirect()->route('admin.templates.prefilled-data.edit', $request->template_id)
                     ->with('success', 'Formato prellenado creado exitosamente.');
}

// El método update() sería casi idéntico, usando $prefilledData->update(...)
// Asegúrate de que en edit(), $prefilledData->data_json se pasa al Blade para el @forelse
// para que los campos dinámicos se prellenen con los datos existentes.
// La validación y construcción del JSON es la misma.
// ...
    // Actualiza un conjunto de datos prellenados existente
    public function update(Request $request, TemplatePrefilledData $prefilledData)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instrumento_tag_id' => ['nullable', 'exists:tags,id'],
            'data_json_raw' => ['required_without:dynamic_data_keys', 'nullable', 'json'],
            'dynamic_data_keys.*' => ['nullable', 'string', 'max:255'],
            'dynamic_data_values.*' => ['nullable', 'string'],
        ]);

        $dataJson = [];
        if ($request->has('data_json_raw')) {
            $dataJson = json_decode($request->input('data_json_raw'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withInput()->withErrors(['data_json_raw' => 'El formato JSON es inválido.']);
            }
        } elseif ($request->has('dynamic_data_keys') && is_array($request->input('dynamic_data_keys'))) {
            $keys = $request->input('dynamic_data_keys');
            $values = $request->input('dynamic_data_values');
            foreach ($keys as $index => $key) {
                if (!empty($key)) {
                    $dataJson[$key] = $values[$index] ?? null;
                }
            }
        }

        DB::transaction(function () use ($request, $prefilledData, $dataJson) {
            $prefilledData->update([
                'name' => $request->name,
                'description' => $request->description,
                'data_json' => $dataJson,
                'instrumento_tag_id' => $request->instrumento_tag_id,
                'is_default_option' => $request->has('is_default_option'),
            ]);
        });

        return redirect()->route('admin.templates.prefilled-data.edit', $prefilledData->template_id)
                         ->with('success', 'Formato prellenado actualizado exitosamente.');
    }

    // ... otros métodos (show, delete) ...
}