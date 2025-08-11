<?php

namespace App\Http\Controllers;

use App\Models\EquipoPatron;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Para el usuario autenticado
use Spatie\Activitylog\Models\Activity; // Para el registro de actividad
use Illuminate\Support\Facades\DB;

class EquipoPatronController extends Controller
{
    /**
     * Constructor del controlador.
     * Protegerá las rutas para administradores o usuarios con permiso.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EquipoPatron::query();

        // --- Filtros y Búsqueda ---
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('identificador', 'like', '%' . $search . '%')
                  ->orWhere('descripcion', 'like', '%' . $search . '%')
                  ->orWhere('marca', 'like', '%' . $search . '%')
                  ->orWhere('modelo', 'like', '%' . $search . '%')
                  ->orWhere('numero_serie', 'like', '%' . $search . '%');
            });
        }

        $filterVigente = $request->input('vigente');
        if ($filterVigente !== null && ($filterVigente === '1' || $filterVigente === '0')) {
            $query->where('vigente', (bool)$filterVigente);
        }

        $filterStatus = $request->input('status'); // 'trashed', 'with_trashed'
        if ($filterStatus) {
            if ($filterStatus === 'trashed') $query->onlyTrashed();
            elseif ($filterStatus === 'with_trashed') $query->withTrashed();
        } else {
            $query->whereNull('deleted_at'); // Default: solo no eliminados
        }

        $equipos = $query->orderBy('identificador')->paginate(15);

        return view('equipos_patrones.index', [
            'equipos' => $equipos,
            'search_query' => $search,
            'selected_vigente' => $filterVigente,
            'selected_status' => $filterStatus,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('equipos_patrones.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'identificador' => ['required', 'string', 'max:255', 'unique:equipos_patrones,identificador'],
            'descripcion' => ['nullable', 'string'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'numero_serie' => ['nullable', 'string', 'max:255', 'unique:equipos_patrones,numero_serie'],
            'ultima_calibracion' => ['nullable', 'date'], // Puede ser nula si no se ha calibrado aún
        ]);

        // --- Lógica de Cálculo de Vigencia ---
        $ultimaCalibracion = $request->input('ultima_calibracion');
        $proximaCalibracion = null;
        $vigente = false;

        if ($ultimaCalibracion) {
            $proximaCalibracion = \Carbon\Carbon::parse($ultimaCalibracion)->addYear(); // 1 año de vigencia
            $vigente = $proximaCalibracion->isFuture(); // Es vigente si la fecha futura aún no ha pasado
        }
        // --- Fin Lógica de Cálculo ---

        DB::transaction(function () use ($request, $proximaCalibracion, $vigente) {
            $equipo = EquipoPatron::create([
                'identificador' => $request->identificador,
                'descripcion' => $request->descripcion,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'numero_serie' => $request->numero_serie,
                'ultima_calibracion' => $request->ultima_calibracion,
                'proxima_calibracion' => $proximaCalibracion,
                'vigente' => $vigente,
                'created_by_user_id' => Auth::id(),
            ]);

            Activity::performedOn($equipo)
                ->causedBy(Auth::user())
                ->event('equipo_patron_created')
                ->log('creó el equipo patrón: "' . $equipo->identificador . '".');
        });

        return redirect()->route('equipos_patrones.index')->with('success', 'Equipo patrón creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EquipoPatron $equipoPatron)
    {
        return view('equipos_patrones.show', compact('equipoPatron'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EquipoPatron $equipoPatron)
    {
        return view('equipos_patrones.edit', compact('equipoPatron'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EquipoPatron $equipoPatron)
    {
        $request->validate([
            'identificador' => ['required', 'string', 'max:255', 'unique:equipos_patrones,identificador,' . $equipoPatron->id],
            'descripcion' => ['nullable', 'string'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'numero_serie' => ['nullable', 'string', 'max:255', 'unique:equipos_patrones,numero_serie,' . $equipoPatron->id],
            'ultima_calibracion' => ['nullable', 'date'],
        ]);

        // --- Lógica de Cálculo de Vigencia (similar a store) ---
        $ultimaCalibracion = $request->input('ultima_calibracion');
        $proximaCalibracion = null;
        $vigente = false;

        if ($ultimaCalibracion) {
            $proximaCalibracion = \Carbon\Carbon::parse($ultimaCalibracion)->addYear();
            $vigente = $proximaCalibracion->isFuture();
        }
        // --- Fin Lógica de Cálculo ---

        DB::transaction(function () use ($request, $equipoPatron, $proximaCalibracion, $vigente) {
            $equipoPatron->update([
                'identificador' => $request->identificador,
                'descripcion' => $request->descripcion,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'numero_serie' => $request->numero_serie,
                'ultima_calibracion' => $request->ultima_calibracion,
                'proxima_calibracion' => $proximaCalibracion,
                'vigente' => $vigente,
                // created_by_user_id no se actualiza
            ]);

            Activity::performedOn($equipoPatron)
                ->causedBy(Auth::user())
                ->event('equipo_patron_updated')
                ->log('actualizó el equipo patrón: "' . $equipoPatron->identificador . '".');
        });

        return redirect()->route('equipos_patrones.index')->with('success', 'Equipo patrón actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EquipoPatron $equipoPatron)
    {
        DB::transaction(function () use ($equipoPatron) {
            $equipoPatron->delete(); // Soft delete
            Activity::performedOn($equipoPatron)
                ->causedBy(Auth::user())
                ->event('equipo_patron_soft_deleted')
                ->log('eliminó (soft delete) el equipo patrón: "' . $equipoPatron->identificador . '".');
        });

        return redirect()->route('equipos_patrones.index')->with('success', 'Equipo patrón eliminado suavemente.');
    }
    
     /**
     * Restore a soft-deleted resource.
     */
    public function restore($id)
    {
        $equipoPatron = EquipoPatron::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($equipoPatron) {
            $equipoPatron->restore();
            Activity::performedOn($equipoPatron)
                ->causedBy(Auth::user())
                ->event('equipo_patron_restored')
                ->log('restauró el equipo patrón: "' . $equipoPatron->identificador . '".');
        });

        return redirect()->route('equipos_patrones.index')->with('success', 'Equipo patrón restaurado exitosamente.');
    }

    /**
     * Permanently remove the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $equipoPatron = EquipoPatron::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($equipoPatron) {
            $equipoPatron->forceDelete();
            Activity::performedOn(null)
                ->causedBy(Auth::user())
                ->event('equipo_patron_force_deleted')
                ->log('eliminó PERMANENTEMENTE el equipo patrón: "' . $equipoPatron->identificador . '".');
        });

        return redirect()->route('equipos_patrones.index')->with('success', 'Equipo patrón eliminado permanentemente.');
    }
}
