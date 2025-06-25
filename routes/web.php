<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
// routes/web.php
 // Importa tu controlador
    
// Ruta para mostrar usuarios pendientes
Route::get('/users/pending', [AdminController::class, 'showPendingUsers'])->name('admin.users.pending');

Route::get('/users/all', [AdminController::class, 'manageUsers'])->name('admin.manage_users');

    // Ruta para aprobar un usuario (Usamos el ID del usuario en la URL)
    // El 'User $user' en el método del controlador automáticamente busca el usuario por el ID
Route::post('/users/{user}/approve', [AdminController::class, 'approveUser'])->name('admin.users.approve');

    // Ruta opcional para rechazar un usuario
Route::post('/users/{user}/reject', [AdminController::class, 'rejectUser'])->name('admin.users.reject');