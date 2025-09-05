<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Diagram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // Para gestionar archivos en storage
use Spatie\Activitylog\Models\Activity; // Para el registro de actividad
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Unidad;
use App\Models\Automata;
use App\Models\DiagramClassification;
use App\Models\Sistema;

class DiagramController extends Controller
{
    /**
     * Constructor del controlador.
     * Aplica middlewares para proteger las rutas de administración de diagramas.
     */
    public function __construct()
    {
        $this->middleware('auth'); // Solo usuarios autenticados
        // Requiere el rol 'admin' O el permiso 'manage diagrams'
        $this->middleware('role:admin|permission:manage diagrams');
    }
    /**
     * Muestra una lista paginada de todos los diagramas y manuales.
     * Incluye opciones de búsqueda, filtro y visualización de soft-deleted.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Diagram::query();

        // Si el admin quiere ver también los eliminados suavemente
        if ($request->has('trashed') && $request->get('trashed') == 'false') {
            $query->withTrashed();
        } elseif ($request->has('trashed') && $request->get('trashed') == 'true') {
            $query->onlyTrashed();
        } else {
            // Por defecto, solo activos (no soft-deleted)
            // Y activos por el campo is_active
            $query->where('is_active', true);
        }

        // --- Lógica de Búsqueda ---
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('machine_category', 'like', '%' . $search . '%')
                  ->orWhere('file_original_name', 'like', '%' . $search . '%');
            });
        }

        // --- Lógica de Filtro por Tipo (diagram/manual) ---
        $filterType = $request->input('type');
        if ($filterType && in_array($filterType, ['diagram', 'manual'])) {
            $query->where('type', $filterType);
        }

        // --- Lógica de Filtro por Categoría de Máquina ---
        $filterCategory = $request->input('category');
        if ($filterCategory) {
            $query->where('machine_category', $filterCategory);
        }

        // Obtener los diagramas paginados
        $diagrams = $query->orderBy('name')->paginate(15);

        // Obtener todas las categorías de máquina únicas para el filtro (solo de diagramas activos)
        $availableCategories = Diagram::where('is_active', true)
                                      ->distinct('machine_category')
                                      ->pluck('machine_category')
                                      ->filter()
                                      ->sort();

        return view('admin.diagrams.index', [
            'diagrams' => $diagrams,
            'search_query' => $search,
            'selected_type' => $filterType,
            'selected_category' => $filterCategory,
            'available_categories' => $availableCategories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
        // Puedes pasarle un listado de categorías si las vas a tener fijas en un select
        /*
        $availableCategories = Diagram::distinct('machine_category')->pluck('machine_category')->filter()->sort();
        return view('admin.diagrams.create', compact('availableCategories'));
        */
        return view('admin.diagrams.create', [
            'unidades' => Unidad::orderBy('unidad')->get(['id','unidad']),
            'classifications' => DiagramClassification::orderBy('name')->get(['id','name']),
            'automatas' => Automata::orderBy('name')->get(['id','name']),
            'sistemas' => Sistema::orderBy('sistema')->get(['id','clave','sistema']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:diagram,manual'],
            'machine_category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'diagram_file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,gif,svg'], // Max 10MB
            'unidad_id' => ['nullable','integer','exists:unidades,id'],
            'classification_id' => ['nullable','integer','exists:diagram_classifications,id'],
            'automata_id' => ['nullable','integer','exists:automatas,id'],
            'sistema_id' => ['nullable','integer','exists:sistemas,id'],
        ]);

        // Manejo del archivo subido
        $filePath = $request->file('diagram_file')->store('diagrams', 'public'); // Guarda en storage/app/public/diagrams/
        $originalFileName = $request->file('diagram_file')->getClientOriginalName();

        $diagram = null; // Inicializar $diagram para el bloque catch

        try {
            DB::transaction(function () use ($request, $filePath, $originalFileName, &$diagram) { // Pass $diagram by reference
                $diagram = Diagram::create([
                    'name' => $request->name,
                    'file_path' => $filePath,
                    'file_original_name' => $originalFileName,
                    'type' => $request->type,
                    'machine_category' => $request->machine_category,
                    'description' => $request->description,
                    'is_active' => $request->boolean('is_active', true), // Default true if checkbox unchecked
                    'created_by_user_id' => Auth::id(),
                    'unidad_id' => $request->unidad_id,
                    'classification_id' => $request->classification_id,
                    'automata_id' => $request->automata_id,
                    'sistema_id' => $request->sistema_id
                ]);

                // Registro de actividad
                activity()
                    ->performedOn($diagram)
                    ->causedBy(Auth::user())
                    ->event('diagram_uploaded')
                    ->log('subió el ' . $diagram->type . ': "' . $diagram->name . '".');
            });
        } catch (\Exception $e) {
            // Si falla la transacción, intenta borrar el archivo que se pudo haber guardado
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            Log::error('Error al subir diagrama: ' . $e->getMessage(), ['user_id' => Auth::id(), 'file_name' => $originalFileName]);
            return back()->withInput()->with('error', 'Error al subir el archivo: ' . $e->getMessage());
        }

        return redirect()->route('admin.diagrams.index')->with('success', $diagram->type . ' subido exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Diagram $diagram)
    {
        $qrContentUrl = route('diagrams.serve_file', $diagram->id);

        // Generar el código QR como SVG para incrustar en el HTML del PDF (Paso 3.2)
        // Lo generamos aquí para pasarlo a la vista si se necesita mostrar en admin.diagrams.show
        $qrSvg = QrCode::size(200)->format('svg')->generate($qrContentUrl);

        return view('admin.diagrams.show', compact('diagram', 'qrSvg', 'qrContentUrl'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Diagram $diagram)
    {
        /*
        $availableCategories = Diagram::distinct('machine_category')->pluck('machine_category')->filter()->sort();
        return view('admin.diagrams.edit', compact('diagram', 'availableCategories'));
        */
        return view('admin.diagrams.edit', [
            'diagram' => $diagram->load(['unidad','classification','automata']),
            'unidades' => Unidad::orderBy('unidad')->get(['id','unidad']),
            'classifications' => DiagramClassification::orderBy('name')->get(['id','name']),
            'automatas' => Automata::orderBy('name')->get(['id','name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Diagram $diagram)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:diagram,manual'],
            'machine_category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'diagram_file' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,gif,svg', 'max:10240'], // Opcional: para reemplazar archivo
            'unidad_id' => ['nullable','integer','exists:unidades,id'],
            'classification_id' => ['nullable','integer','exists:diagram_classifications,id'],
            'automata_id' => ['nullable','integer','exists:automatas,id'],
        ]);

        $filePath = $diagram->file_path; // Mantener el path actual por defecto
        $originalFileName = $diagram->file_original_name;

        // Si se sube un nuevo archivo, reemplazar el existente
        if ($request->hasFile('diagram_file')) {
            // Eliminar el archivo antiguo del storage
            if (Storage::disk('public')->exists($diagram->file_path)) {
                Storage::disk('public')->delete($diagram->file_path);
            }
            // Guardar el nuevo archivo
            $filePath = $request->file('diagram_file')->store('diagrams', 'public');
            $originalFileName = $request->file('diagram_file')->getClientOriginalName();
        }

        DB::transaction(function () use ($request, $diagram, $filePath, $originalFileName) {
            $diagram->update([
                'name' => $request->name,
                'file_path' => $filePath,
                'file_original_name' => $originalFileName,
                'type' => $request->type,
                'machine_category' => $request->machine_category,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', false),
                'created_by_user_id' => Auth::id(), // Registrar quién lo actualizó, o dejar al original
                'unidad_id' => $request->unidad_id,
                'classification_id' => $request->classification_id,
                'automata_id' => $request->automata_id
            ]);

            activity()
                ->performedOn($diagram)
                ->causedBy(Auth::user())
                ->event('diagram_updated')
                ->log('actualizó el ' . $diagram->type . ': "' . $diagram->name . '".');
        });

        return redirect()->route('admin.diagrams.index')->with('success', $diagram->type . ' actualizado exitosamente.');
    }

    /**
 * Remove the specified resource from storage.
 */
    public function destroy(Diagram $diagram, $id)
    {
        Diagram::find($id)->delete();

        activity()
            ->performedOn($diagram)
            ->causedBy(Auth::user())
            ->log('soft deleted diagram "' . $diagram->name . '".');

        return redirect()->route('admin.diagrams.index')->with('success', $diagram->type . ' eliminado suavemente.');
    }
    public function restore($id)
    {
        $diagram = Diagram::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($diagram) {
            $diagram->restore();
            activity()
                ->performedOn($diagram)
                ->causedBy(Auth::user())
                ->event('diagram_restored')
                ->log('restauró el ' . $diagram->type . ': "' . $diagram->name . '".');
        });

        return redirect()->route('admin.diagrams.index')->with('success', $diagram->type . ' restaurado exitosamente.');
    }

    /**
     * Elimina permanentemente un diagrama/manual (incluyendo el archivo físico).
     * ¡CUIDADO! Romperá referencias si se usa irresponsablemente.
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function force_delete($id)
    {
        $diagram = Diagram::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($diagram) {
            // Eliminar el archivo físico del storage
            if (Storage::disk('public')->exists($diagram->file_path)) {
                Storage::disk('public')->delete($diagram->file_path);
            }

            activity()
                ->performedOn($diagram) // Pass the diagram model before deleting
                ->causedBy(Auth::user())
                ->event('diagram_force_deleted')
                ->log('eliminó PERMANENTEMENTE el ' . $diagram->type . ': "' . $diagram->name . '".');
            $diagram->forceDelete(); // Elimina permanentemente el registro de la DB
        });

        return redirect()->route('admin.diagrams.index')->with('success', $diagram->type . ' eliminado permanentemente.');
    }
}
