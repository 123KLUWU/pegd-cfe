<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Verifica si el usuario está autorizado
        if ($user->status !== 'Activo') {
            // Si no está autorizado, cierra la sesión del usuario
            Auth::logout();

            // Invalida la sesión y regenera el token CSRF para seguridad
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Mensaje más específico basado en el estado
            $errorMessage = 'Tu cuenta aún no ha sido autorizada. Por favor, espera la aprobación del administrador.';
            if ($user->status === 'Rechazado') {
                $errorMessage = 'Tu solicitud de cuenta ha sido rechazada. Por favor, contacta al administrador.';
            }

            throw ValidationException::withMessages([
                $this->username() => [$errorMessage],
            ]);
        }
        activity()
            ->performedOn($user) // El usuario sobre el que se realizó la acción (opcional, pero útil)
            ->causedBy(Auth::user()) // El usuario que realizó la acción (en este caso, el mismo que inició sesión)
            ->event('login') // Nombre del evento (ej. 'login', 'logout', 'failed_login')
            ->log('inició sesión en el sistema.'); // Descripción de la actividad

        // Si el usuario está autorizado, permite que la autenticación continúe
        // y lo redirige a $redirectTo
        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        // Asegúrate de que esto devuelve 'erp_key' si es lo que usas para login
        // En lugar de 'email'
        return 'rpe'; // <-- ¡IMPORTANTE: Cambia 'email' por 'erp_key' aquí si es tu campo de login!
    }
}
