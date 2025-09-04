<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }
    // Método para mostrar las solicitudes de usuario pendientes
    public function showPendingUsers()
    {
        // Filtra los usuarios donde 'is_authorized' es falso
        $pendingUsers = User::where('status', 'Pendiente')->get();
        return view('admin.pending_users', compact('pendingUsers'));
    }

    /**
     * Aprobar la cuenta de un usuario pendiente y asignarle el rol 'employee'.
     *
     * @param User $user La instancia del usuario a aprobar (Route Model Binding).
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveUser(User $user)
    {
        // Usamos una transacción de base de datos para asegurar atomicidad
        // (Si una parte falla, todo se revierte)
        DB::transaction(function () use ($user) {
            // 1. Cambiar el estado a true
            $user->status = 'Activo';
            $user->save();
            
            if (!$user->hasRole('admin')) { // <-- Verifica si ya no es admin
                $user->assignRole('employee'); // <-- ¡Aquí se asigna el rol!
            }

            activity()
            ->performedOn($user) // El usuario que fue aprobado (el sujeto)
            ->causedBy(Auth()->user()) // El administrador que aprobó (el causante)
            ->event('user_approved')
            ->log('aprobó la cuenta del usuario ' . $user->name . ' (RPE: ' . $user->rpe . ').');
        });

        // Redirigir de vuelta a la lista de usuarios pendientes con un mensaje de éxito
        return redirect()->route('admin.users.pending')->with('success', 'Usuario ' . $user->name . ' ha sido autorizado y aprobado exitosamente.');
    }

    // Opcional: Método para rechazar/desautorizar un usuario
    public function rejectUser(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->status = 'Rechazado';
            $user->save();

            // Log de actividad
            activity()
                ->performedOn($user)
                ->causedBy(Auth()->user())
                ->event('user_Rechazado')
                ->log('rechazó/desautorizó la cuenta del usuario ' . $user->name . ' (RPE: ' . $user->rpe . ').');

        });
        return redirect()->route('admin.users.pending')->with('success', 'Usuario ' . $user->name . ' ha sido rechazado.');
    }
    public function manageUsers(User $user)
    {
        $allUsers = User::all();
        return view('admin.manage_users', compact('allUsers'));
        /*
        DB::transaction(function () use ($user) {
            $user->status = 'Rechazado';
            $user->save();

            // Log de actividad
            activity()
                ->performedOn($user)
                ->causedBy(Auth()->user())
                ->event('user_Rechazado')
                ->log('rechazó/desautorizó la cuenta del usuario ' . $user->name . ' (RPE: ' . $user->rpe . ').');

        });

        return redirect()->route('admin.users.pending')->with('success', 'Usuario ' . $user->name . ' ha sido rechazado.');
        */
    }
    public function index()
    {
        // Puedes pasar conteos, últimas actualizaciones, etc.
        // $stats = [...];
        return view('admin.index'); // resources/views/admin/index.blade.php
    }
}