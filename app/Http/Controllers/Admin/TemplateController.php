<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template; // Tu modelo de plantilla
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity; // Para logs
use Illuminate\Support\Facades\DB; // Para transacciones

class TemplateController extends Controller
{
    /**
     * Constructor del controlador.
     * Protege las rutas para administradores con el permiso adecuado.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // El usuario debe tener el rol 'admin' O el permiso 'manage templates'.
        $this->middleware('role:admin|permission:manage templates');
    }

    /**
     * Muestra la lista de todas las plantillas (para administradores).
     * Esto es diferente del TemplateController@index para usuarios normales.
     */
    public function index()
    {
        // Con withTrashed() para ver también las soft-deleted
        $templates = Template::withTrashed()->get();
        return view('admin.templates.index', compact('templates'));
    }

    /**
     * Muestra el formulario para crear una nueva plantilla.
     */
    public function create()
    {
        return view('admin.templates.create');
    }

    /**
     * Almacena una nueva plantilla en la base de datos.
     * Aquí se guarda el `mapping_rules_json` inicial.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:templates,name'],
            'google_drive_id' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:docs,sheets'], // Valida que sea 'docs' o 'sheets'
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'], // Para el checkbox
            'mapping_rules_json_raw' => ['nullable', 'json'], // JSON crudo del textarea
            'dynamic_keys.*' => ['nullable', 'string', 'max:255'], // Claves lógicas
            'dynamic_values.*' => ['nullable', 'string'], // Ubicaciones físicas (celdas/marcadores)
        ]);

        $mappingRules = [];
        $keys = $request->input('dynamic_keys');
        $values = $request->input('dynamic_values');

        if ($keys && is_array($keys)) {
            foreach ($keys as $index => $key) {
                if (!empty($key)) {
                    $mappingRules[$key] = $values[$index] ?? null;
                }
            }
        }
        if (empty($mappingRules)) {
            return back()->withInput()->withErrors(['dynamic_keys' => 'Debe añadir al menos un par clave-valor para las reglas de mapeo.']);
        }
        
        DB::transaction(function () use ($request, $mappingRules) {
            $template = Template::create([
                'name' => $request->name,
                'google_drive_id' => $request->google_drive_id,
                'type' => $request->type,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active'), // Laravel convierte 0/1 o true/false
                'mapping_rules_json' => $mappingRules, // Laravel guardará el array PHP como JSON
                'created_by_user_id' => Auth::id(),
            ]);

            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_created')
                ->log('creó la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla creada exitosamente.');
    }

    /**
     * Muestra el formulario para editar una plantilla existente.
     * Incluye el `mapping_rules_json` actual.
     */
    public function edit(Template $template) // Route Model Binding
    {
        // Codifica el JSON de reglas de mapeo para mostrarlo en el textarea
        $template->mapping_rules_json_raw = json_encode($template->mapping_rules_json, JSON_PRETTY_PRINT);
        return view('admin.templates.edit', compact('template'));
    }

    /**
     * Actualiza una plantilla existente en la base de datos.
     * Aquí se actualiza el `mapping_rules_json`.
     */
    public function update(Request $request, Template $template)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:templates,name,' . $template->id], // Ignora el propio ID al validar unique
            'google_drive_id' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:docs,sheets'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'mapping_rules_json_raw' => ['nullable', 'json'],
        ]);

        $mappingRules = json_decode($request->input('mapping_rules_json_raw'), true);
        if (json_last_error() !== JSON_ERROR_NONE && $request->filled('mapping_rules_json_raw')) {
            return back()->withInput()->withErrors(['mapping_rules_json_raw' => 'El formato JSON de las reglas de mapeo es inválido.']);
        }

        DB::transaction(function () use ($request, $template, $mappingRules) {
            $template->update([
                'name' => $request->name,
                'google_drive_id' => $request->google_drive_id,
                'type' => $request->type,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active'),
                'mapping_rules_json' => $mappingRules,
            ]);

            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_updated')
                ->log('actualizó la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla actualizada exitosamente.');
    }

    /**
     * "Elimina" suavemente una plantilla.
     */
    public function delete(Template $template)
    {
        DB::transaction(function () use ($template) {
            $template->delete(); // Soft delete
            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_deleted')
                ->log('eliminó (soft delete) la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla eliminada suavemente.');
    }

    /**
     * Restaura una plantilla eliminada suavemente.
     */
    public function restore($id)
    {
        $template = Template::withTrashed()->findOrFail($id); // Busca también en los soft-deleted

        DB::transaction(function () use ($template) {
            $template->restore(); // Restaura
            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_restored')
                ->log('restauró la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla restaurada exitosamente.');
    }

    /**
     * Elimina permanentemente una plantilla.
     * ¡CUIDADO! Romperá referencias en generated_documents.
     */
    public function forceDelete($id)
    {
        $template = Template::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($template) {
            $template->forceDelete(); // Elimina permanentemente
            activity()
                ->performedOn($template)
                ->causedBy(Auth::user())
                ->event('template_force_deleted')
                ->log('eliminó PERMANENTEMENTE la plantilla: "' . $template->name . '".');
        });

        return redirect()->route('admin.templates.index')->with('success', 'Plantilla eliminada permanentemente.');
    }
}