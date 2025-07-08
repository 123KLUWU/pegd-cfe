<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Tu modelo de usuario
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role; // Para gestionar roles
use Spatie\Permission\Models\Permission; // Para gestionar permisos
use Spatie\Activitylog\Models\Activity; // Para el registro de actividad
use App\Models\GeneratedDocument; // Para ver documentos generados por el usuario

class UserController extends Controller
{
    /**
     * Constructor. Protege las rutas del admin.
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin|permission:manage users'); // Solo administradores o con permiso
    }

    /**
     * Muestra una lista de todos los usuarios con búsqueda, filtros y paginación.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // --- Filtros de Estado ---
        $filterStatus = $request->input('status');
        if ($filterStatus && in_array($filterStatus, ['Pendiente', 'Activo', 'Rechazado', 'inactive', 'trashed', 'with_trashed'])) {
            if ($filterStatus === 'trashed') {
                $query->onlyTrashed();
            } elseif ($filterStatus === 'with_trashed') {
                $query->withTrashed();
            } else {
                $query->where('status', $filterStatus);
            }
        } else {
            // Por defecto, solo usuarios no eliminados y activos o pendientes
            $query->where(function ($q) {
                $q->where('status', 'Activo')
                  ->orWhere('status', 'Pendiente'); // Mostrar pendientes para revisión en la misma tabla
            })->whereNull('deleted_at'); // Solo no soft-deleted por defecto
        }

        // --- Búsqueda ---
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('rpe', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%'); // Si tienes email
            });
        }

        // --- Filtro por Rol ---
        $filterRole = $request->input('role');
        if ($filterRole && Role::where('name', $filterRole)->exists()) {
            $query->role($filterRole); // Método de Spatie para filtrar por rol
        }

        $users = $query->orderBy('name')->paginate(15);
        $roles = Role::all(); // Todos los roles para el filtro

        return view('admin.users.index', [
            'users' => $users,
            'search_query' => $search,
            'selected_status' => $filterStatus,
            'selected_role' => $filterRole,
            'available_roles' => $roles,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario (quizás solo para admin).
     */
    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Almacena un nuevo usuario (creado por admin).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rpe' => ['required', 'string', 'max:5', 'unique:users,rpe'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'], // Si usas email
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:Pendiente,Activo,Rechazado,Inactivo'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'], // Asegura que los roles existan
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'rpe' => $request->rpe,
                'email' => $request->email, // Si usas email
                'password' => Hash::make($request->password),
                'status' => $request->status,
                // 'is_authorized' => $request->status === 'Activo' ? true : false, // Mantener is_authorized si aún lo tienes y es necesario
                // 'created_by_user_id' => Auth::id(), // Si quieres registrar quién lo creó
            ]);

            $user->syncRoles($request->input('roles', [])); // Asigna los roles seleccionados

            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->event('user_created_by_admin')
                ->log('creó el usuario: "' . $user->name . '" con RPE: ' . $user->rpe . '.');
        });

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Muestra los detalles de un usuario (para ver o gestionar).
     * Puedes integrar aquí la actividad y documentos generados.
     */
    public function show(User $user)
    {
        // Puedes pasar datos para la sección de actividad y documentos generados del usuario
        $userActivities = Activity::where('causer_id', $user->id)->latest()->paginate(10, ['*'], 'activities_page');
        $userGeneratedDocuments = GeneratedDocument::where('user_id', $user->id)->latest()->paginate(10, ['*'], 'documents_page');

        return view('admin.users.show', compact('user', 'userActivities', 'userGeneratedDocuments'));
    }

    /**
     * Muestra el formulario para editar un usuario existente.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        // Para preseleccionar roles en el formulario
        $userRoles = $user->getRoleNames();
        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Actualiza la información y los roles de un usuario existente.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rpe' => ['required', 'string', 'max:5', 'unique:users,rpe,' . $user->id],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'], // Contraseña opcional para cambiar
            'status' => ['required', 'in:Pendiente,Activo,Rechazado,Inactivo'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,name'],
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->update([
                'name' => $request->name,
                'rpe' => $request->rpe,
                'email' => $request->email,
                'status' => $request->status,
                // 'is_authorized' => $request->status === 'Activo' ? true : false, // Mantener is_authorized si aún lo tienes
                // created_by_user_id no se actualiza, es solo al crear
            ]);

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
                $user->save();
            }

            $user->syncRoles($request->input('roles', [])); // Actualiza los roles

            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->event('user_updated')
                ->log('actualizó el usuario: "' . $user->name . '" (RPE: ' . $user->rpe . ').');
        });

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Elimina lógicamente un usuario (soft delete).
     */
    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->delete(); // Soft delete
            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->event('user_soft_deleted')
                ->log('eliminó (soft delete) al usuario: "' . $user->name . '" (RPE: ' . $user->rpe . ').');
        });

        return redirect()->route('users.index')->with('success', 'Usuario eliminado suavemente.');
    }

    /**
     * Restaura un usuario soft-deleted.
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id); // Buscar solo entre los eliminados

        DB::transaction(function () use ($user) {
            $user->restore();
            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->event('user_restored')
                ->log('restauró al usuario: "' . $user->name . '" (RPE: ' . $user->rpe . ').');
        });

        return redirect()->route('users.index')->with('success', 'Usuario restaurado exitosamente.');
    }

    /**
     * Elimina permanentemente un usuario (y su registro).
     * ¡CUIDADO! Romperá referencias si no se maneja bien.
     */
    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id); // Buscar entre todos

        DB::transaction(function () use ($user) {
            // Si hay relaciones que deben limpiarse, hazlo aquí antes del forceDelete.
            // Por ejemplo, si un diagrama solo lo puede crear un usuario, y quieres borrar los diagramas del usuario,
            // pero si tu relación es onDelete('set null') para generated_documents.user_id, no pasa nada.

            $user->forceDelete(); // Elimina permanentemente
            activity()
                ->performedOn(null) // No hay sujeto, ya que el usuario se va a borrar
                ->causedBy(Auth::user())
                ->event('user_force_deleted')
                ->log('eliminó PERMANENTEMENTE al usuario: "' . $user->name . '" (RPE: ' . $user->rpe . ').');
        });

        return redirect()->route('users.index')->with('success', 'Usuario eliminado permanentemente.');
    }

    /**
     * Método para aprobar un usuario pendiente (si se usa desde la misma tabla de usuarios).
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveUser(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->status = 'Activo';
            // $user->is_authorized = true; // Si aún usas is_authorized
            $user->save();

            // Asignar rol 'employee' si no tiene ya un rol (ej. 'admin')
            if ($user->roles->isEmpty()) { // Si el usuario no tiene ningún rol
                $user->assignRole('employee');
            }

            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->event('user_approved')
                ->log('aprobó la cuenta del usuario: "' . $user->name . '" (RPE: ' . $user->rpe . ').');
        });

        return redirect()->route('users.index')->with('success', 'Usuario ' . $user->name . ' aprobado exitosamente.');
    }

    /**
     * Método para rechazar un usuario pendiente.
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function rejectUser(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->status = 'Rechazado';
            // $user->is_authorized = false; // Si aún usas is_authorized
            $user->save();

            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->event('user_Rechazado')
                ->log('rechazó la cuenta del usuario: "' . $user->name . '" (RPE: ' . $user->rpe . ').');
        });

        return redirect()->route('users.index')->with('success', 'Usuario ' . $user->name . ' rechazado.');
    }
}