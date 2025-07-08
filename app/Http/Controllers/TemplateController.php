<?php

namespace App\Http\Controllers;

use App\Models\Template; // ¡Importa tu modelo Template!
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Opcional, si lo necesitas para algo en este controlador

class TemplateController extends Controller
{
    /**
     * Constructor del controlador.
     * Aplica middlewares para proteger las rutas.
     */
    public function __construct()
    {
        // Asegura que solo usuarios autenticados puedan acceder a estas rutas.
        $this->middleware('auth');
        // Opcional: Si usas spatie/laravel-permission y quieres que solo usuarios con un permiso
        // específico puedan ver las plantillas, puedes añadirlo aquí:
        // $this->middleware('permission:view templates');
    }

    /**
     * Muestra una lista de todas las plantillas disponibles para los usuarios.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Recupera todas las plantillas de la base de datos que estén activas.
        // Asume que el campo 'is_active' existe en tu tabla 'templates'.
        $templates = Template::where('is_active', true)->get();

        // Retorna la vista 'templates.index' y le pasa las plantillas.
        return view('templates.index', compact('templates'));
    }
}