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
use App\Http\Requests\StoreTemplatePrefilledDataRequest;
use App\Http\Requests\UpdateTemplatePrefilledDataRequest;
use App\Models\Sistema;
use App\Models\Servicio;

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
            // Decodifica reglas para mostrar "sin reglas" si aplica (la vista también lo valida)
    $raw   = $template->mapping_rules_json ?? '[]';
    $rules = is_array($raw) ? $raw : json_decode($raw, true);

    // Catálogos para los selects
    $tags      = Tag::orderBy('tag')->get(['id','tag']);
    $sistemas  = Sistema::orderBy('sistema')->get(['id','sistema']);
    $servicios = Servicio::orderBy('servicio')->get(['id','servicio']);
    $unidades  = Unidad::orderBy('unidad')->get(['id','unidad']);

    return view('admin.template_prefilled_data.create', compact(
        'template', 'tags', 'sistemas', 'servicios', 'unidades'
    ));
    }

    // Muestra el formulario para editar un conjunto de datos prellenados existente
    public function edit(TemplatePrefilledData $prefilledData) // Route Model Binding
    {
        $template = $prefilledData->template; // relación belongsTo

        $raw   = $template->mapping_rules_json ?? '[]';
        $rules = is_array($raw) ? $raw : json_decode($raw, true);
        $rules = $rules ?: [];

        $current = $prefilledData->data_json ?? [];
        $ruleKeys    = array_keys($rules);
        $currentKeys = array_keys($current);

        // Detectar claves
        $newKeys      = array_values(array_diff($ruleKeys, $currentKeys));    // nuevas en reglas
        $obsoleteKeys = array_values(array_diff($currentKeys, $ruleKeys));    // sobran en data

        $tags      = Tag::orderBy('tag')->get(['id','tag']);
        $sistemas  = Sistema::orderBy('sistema')->get(['id','sistema']);
        $servicios = Servicio::orderBy('servicio')->get(['id','servicio']);
        $unidades  = Unidad::orderBy('unidad')->get(['id','unidad']);

        return view('admin.template_prefilled_data.edit', compact(
            'prefilledData', 'template', 'rules', 'current', 'newKeys', 'obsoleteKeys',
            'tags', 'sistemas', 'servicios', 'unidades'
        ));
    }
    
    public function store(StoreTemplatePrefilledDataRequest $request, Template $template)
    {
        $validated = $request->validated();

        // Fuente de verdad: claves desde la plantilla
        $raw   = $template->mapping_rules_json ?? '[]';
        $rules = is_array($raw) ? $raw : json_decode($raw, true);
        $rules = $rules ?: [];
        $keys  = array_keys($rules);

        if (empty($keys)) {
            return back()->withInput()->with('error', 'La plantilla no tiene reglas de mapeo.');
        }

        $values = $validated['data_values'] ?? [];
        $dataJson = [];
        foreach ($keys as $k) {
            $dataJson[$k] = array_key_exists($k, $values) && $values[$k] !== '' ? $values[$k] : null;
        }

        // (Opcional) evitar duplicados por combinación (ajusta a tus reglas)
        $exists = TemplatePrefilledData::where('template_id', $template->id)
            ->when(!empty($validated['name']), fn($q) => $q->where('name', $validated['name']))
            ->first();

        if ($exists) {
            return back()->withInput()->with('error', 'Ya existe un prellenado con ese nombre para esta plantilla.');
        }

        TemplatePrefilledData::create([
            'template_id' => $template->id,
            'name'        => $validated['name'] ?? null,
            'tag_id'      => $validated['tag_id'] ?? null,
            'sistema_id'  => $validated['sistema_id'] ?? null,
            'servicio_id' => $validated['servicio_id'] ?? null,
            'unidad_id'   => $validated['unidad_id'] ?? null,
            'data_json'   => $dataJson,
        ]);

        return redirect()->route('admin.templates.prefilled-data.index')->with('success', 'Prellenado creado correctamente.');
    }

    public function update(UpdateTemplatePrefilledDataRequest $request, TemplatePrefilledData $prefilledData)
    {
        $validated = $request->validated();

        $template = $prefilledData->template;
        $raw   = $template->mapping_rules_json ?? '[]';
        $rules = is_array($raw) ? $raw : json_decode($raw, true);
        $rules = $rules ?: [];

        $ruleKeys = array_keys($rules);
        if (empty($ruleKeys)) {
            return back()->withInput()->with('error', 'La plantilla no tiene reglas de mapeo.');
        }

        $values = $validated['data_values'] ?? [];

        // 1) Construir con sólo las claves actuales de la plantilla:
        $dataJson = [];
        foreach ($ruleKeys as $k) {
            $dataJson[$k] = array_key_exists($k, $values) && $values[$k] !== '' ? $values[$k] : null;
        }

        // 2) (Estrategia por defecto) PRESERVAR claves obsoletas ya guardadas
        //    para no perder info histórica, salvo que marques lo contrario.
        //    Si quieres permitir "limpiar" obsoletas al marcar un checkbox, agrega:
        //    if (!$request->boolean('drop_obsolete')) { ... }  (y un checkbox en la vista)
        $current = $prefilledData->data_json ?? [];
        foreach ($current as $k => $v) {
            if (!array_key_exists($k, $dataJson)) {
                $dataJson[$k] = $v; // conservar obsoleto
            }
        }

        $prefilledData->update([
            'name'        => $validated['name'] ?? $prefilledData->name,
            'tag_id'      => $validated['tag_id'] ?? null,
            'sistema_id'  => $validated['sistema_id'] ?? null,
            'servicio_id' => $validated['servicio_id'] ?? null,
            'unidad_id'   => $validated['unidad_id'] ?? null,
            'data_json'   => $dataJson,
        ]);

        return redirect()->route('admin.templates.prefilled-data.index')->with('success', 'Prellenado actualizado.');
    }
}