<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EquipoPatron;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EquipoPatronController extends Controller
{
    /**
     * Constructor del controlador.
     * Protegerá las rutas para administradores o usuarios con permiso.
    */
    public function __construct()
    {
        $this->middleware('auth');
        // Asegúrate de que el rol 'admin' o el permiso 'manage equipos patrones' estén asignados
        $this->middleware('role:admin');
    }

    /**
     * Muestra un listado paginado de los equipos patrones.
     * Incluye opciones de búsqueda y filtro por estado.
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

        $filterEstado = $request->input('estado');
        if ($filterEstado && in_array($filterEstado, ['CUMPLE', 'NO CUMPLE', 'CUMPLE PARCIALMENTE'])) {
            $query->where('estado', $filterEstado);
        }

        $filterStatus = $request->input('status'); // 'trashed', 'with_trashed'
        if ($filterStatus) {
            if ($filterStatus === 'trashed') $query->onlyTrashed();
            elseif ($filterStatus === 'with_trashed') $query->withTrashed();
        } else {
            $query->whereNull('deleted_at'); // Por defecto, solo no soft-deleted
        }

        $equipos = $query->orderBy('identificador')->paginate(15);

        return view('admin.equipos_patrones.index', [
            'equipos' => $equipos,
            'search_query' => $search,
            'selected_estado' => $filterEstado,
            'selected_status' => $filterStatus,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo equipo patrón.
     */
    public function create()
    {
        return view('admin.equipos_patrones.create');
    }

    /**
     * Almacena un nuevo equipo patrón en la base de datos.
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

        // --- Lógica de Cálculo de 'proxima_calibracion' y 'estado' ---
        $ultimaCalibracion = $request->input('ultima_calibracion');
        $proximaCalibracion = null;
        $estado = 'NO CUMPLE'; // Valor por defecto si no hay fecha de calibración

        if ($ultimaCalibracion) {
            $proximaCalibracion = Carbon::parse($ultimaCalibracion)->addYear(); // 1 año de vigencia
            
            if ($proximaCalibracion->isFuture()) {
                // Si la próxima calibración es en el futuro
                if ($proximaCalibracion->diffInDays(now()) <= 30) { // Si faltan 30 días o menos para expirar
                    $estado = 'CUMPLE PARCIALMENTE';
                } else {
                    $estado = 'CUMPLE';
                }
            } else {
                $estado = 'NO CUMPLE'; // La fecha de próxima calibración ya pasó
            }
        }
        // --- Fin Lógica de Cálculo ---

        DB::transaction(function () use ($request, $proximaCalibracion, $estado) {
            $equipo = EquipoPatron::create([
                'identificador' => $request->identificador,
                'descripcion' => $request->descripcion,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'numero_serie' => $request->numero_serie,
                'ultima_calibracion' => $request->ultima_calibracion,
                'proxima_calibracion' => $proximaCalibracion,
                'estado' => $estado, // Asignar el estado calculado
                'created_by_user_id' => Auth::id(),
            ]);

            Activity()->performedOn($equipo)
                ->causedBy(Auth::user())
                ->event('equipo_patron_created')
                ->log('creó el equipo patrón: "' . $equipo->identificador . '".');
        });

        return redirect()->route('admin.equipos-patrones.index')->with('success', 'Equipo patrón creado exitosamente.');
    }

    /**
     * Muestra los detalles de un equipo patrón.
     */
    public function show(EquipoPatron $equipoPatron)
    {
        return view('admin.equipos_patrones.show', compact('equipoPatron'));
    }

    /**
     * Muestra el formulario para editar un equipo patrón existente.
     */
    public function edit(EquipoPatron $equipoPatron)
    {
        return view('admin.equipos_patrones.edit', compact('equipoPatron'));
    }

    /**
     * Actualiza la información de un equipo patrón existente.
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

        // --- Lógica de Cálculo de 'proxima_calibracion' y 'estado' (similar a store) ---
        $ultimaCalibracion = $request->input('ultima_calibracion');
        $proximaCalibracion = null;
        $estado = 'NO CUMPLE';

        if ($ultimaCalibracion) {
            $proximaCalibracion = Carbon::parse($ultimaCalibracion)->addYear();
            
            if ($proximaCalibracion->isFuture()) {
                if ($proximaCalibracion->diffInDays(now()) <= 30) {
                    $estado = 'CUMPLE PARCIALMENTE';
                } else {
                    $estado = 'CUMPLE';
                }
            } else {
                $estado = 'NO CUMPLE';
            }
        }
        // --- Fin Lógica de Cálculo ---

        DB::transaction(function () use ($request, $equipoPatron, $proximaCalibracion, $estado) {
            $equipoPatron->update([
                'identificador' => $request->identificador,
                'descripcion' => $request->descripcion,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'numero_serie' => $request->numero_serie,
                'ultima_calibracion' => $request->ultima_calibracion,
                'proxima_calibracion' => $proximaCalibracion,
                'estado' => $estado, // Asignar el estado calculado
            ]);

            Activity()->performedOn($equipoPatron)
                ->causedBy(Auth::user())
                ->event('equipo_patron_updated')
                ->log('actualizó el equipo patrón: "' . $equipoPatron->identificador . '".');
        });

        return redirect()->route('admin.equipos-patrones.index')->with('success', 'Equipo patrón actualizado exitosamente.');
    }

    /**
     * Elimina lógicamente un equipo patrón (soft delete).
     */
    public function destroy(EquipoPatron $equipoPatron)
    {
        //dd($equipoPatron);
        DB::transaction(function () use ($equipoPatron) {
            $equipoPatron->delete(); // Soft delete
            Activity()->performedOn($equipoPatron)
                ->causedBy(Auth::user())
                ->event('equipo_patron_soft_deleted')
                ->log('eliminó (soft delete) el equipo patrón: "' . $equipoPatron->identificador . '".');
        });

        return redirect()->route('admin.equipos-patrones.index')->with('success', 'Equipo patrón eliminado suavemente.');
    }

    /**
     * Restaura un equipo patrón soft-deleted.
     */
    public function restore($id)
    {
        $equipoPatron = EquipoPatron::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($equipoPatron) {
            $equipoPatron->restore();
            Activity()->performedOn($equipoPatron)
                ->causedBy(Auth::user())
                ->event('equipo_patron_restored')
                ->log('restauró el equipo patrón: "' . $equipoPatron->identificador . '".');
        });

        return redirect()->route('admin.equipos-patrones.index')->with('success', 'Equipo patrón restaurado exitosamente.');
    }

    /**
     * Elimina permanentemente un equipo patrón.
     */
    public function forceDelete($id)
    {
        $equipoPatron = EquipoPatron::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($equipoPatron) {
            $equipoPatron->forceDelete();
            Activity()->performedOn(null)
                ->causedBy(Auth::user())
                ->event('equipo_patron_force_deleted')
                ->log('eliminó PERMANENTEMENTE el equipo patrón: "' . $equipoPatron->identificador . '".');
        });

        return redirect()->route('admin.equipos-patrones.index')->with('success', 'Equipo patrón eliminado permanentemente.');
    }
}
