// app/Http/Controllers/Auth/LoginController.php

// ... otros use statements ...
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity; // Opcional, el helper activity() no lo necesita directamente


class LoginController extends Controller
{
    use AuthenticatesUsers;

    // ... tu constructor y $redirectTo ...

    protected function authenticated(Request $request, $user)
    {
        if (!$user->is_authorized) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                $this->username() => ['Tu cuenta aún no ha sido autorizada. Por favor, espera la aprobación del administrador.'],
            ]);
        }

        // --- AÑADE ESTO PARA REGISTRAR EL INICIO DE SESIÓN EXITOSO ---
        activity()
            ->performedOn($user) // El usuario sobre el que se realizó la acción (opcional, pero útil)
            ->causedBy(Auth::user()) // El usuario que realizó la acción (en este caso, el mismo que inició sesión)
            ->event('login') // Nombre del evento (ej. 'login', 'logout', 'failed_login')
            ->log('inició sesión en el sistema.'); // Descripción de la actividad

        return redirect()->intended($this->redirectPath());
    }

    // --- OPCIONAL: Registrar intentos fallidos de inicio de sesión ---
    protected function sendFailedLoginResponse(Request $request)
    {
        activity()
            ->event('failed_login')
            ->withProperties(['rpe_attempt' => $request->input($this->username()), 'ip_address' => $request->ip()])
            ->log('intentó iniciar sesión con credenciales inválidas.');

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
// app/Http/Controllers/AdminController.php

// ...
// use Spatie\Activitylog\Models\Activity; // No es estrictamente necesario importar Activity si usas el helper activity()
// ...

public function approveUser(User $user)
{
    DB::transaction(function () use ($user) {
        $user->is_authorized = true;
        $user->save();

        if (!$user->hasRole('admin')) {
            $user->assignRole('employee');
        }

        // --- REGISTRO DE ACTIVIDAD DE APROBACIÓN ---
        activity()
            ->performedOn($user) // El usuario que fue aprobado (el sujeto)
            ->causedBy(Auth()->user()) // El administrador que aprobó (el causante)
            ->event('user_approved')
            ->log('aprobó la cuenta del usuario ' . $user->name . ' (RPE: ' . $user->rpe . ').');
    });

    return redirect()->route('admin.users.pending')->with('success', 'Usuario ' . $user->name . ' ha sido autorizado y aprobado exitosamente.');
}