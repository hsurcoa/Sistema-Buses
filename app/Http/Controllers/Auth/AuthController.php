<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\LegacyRbacSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Login/logout, 100% Laravel nativo (Fase 1, Tarea 8; puente de sesion
 * nativa eliminado al cerrar la migracion — ver informe de fin de sesion).
 * El guard de Laravel (`Auth::login()`/`auth()->user()`) es la unica fuente
 * de verdad de la sesion; ya no existe `SessionManager` ni una cookie nativa
 * de PHP en paralelo.
 */
class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request, LegacyRbacSync $rbacSync): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = Usuario::where('email', $credentials['email'])->first();

        if (! $usuario || ! Hash::check($credentials['password'], $usuario->password)) {
            return back()
                ->withInput($request->only('email'))
                ->with('mensaje_error', 'Credenciales inválidas.');
        }

        // Cuenta desactivada por un administrador: no puede entrar
        if ($usuario->estado !== 'activo') {
            return back()
                ->withInput($request->only('email'))
                ->with('mensaje_error', 'Su cuenta está desactivada. Consulte con el administrador.');
        }

        $request->session()->regenerate();
        Auth::login($usuario);

        // RBAC spatie al dia con lo que el admin haya cambiado en Admin >
        // Roles y permisos (Tarea 9).
        try {
            $rbacSync->syncUser($usuario);
        } catch (\Throwable $e) {
            Log::error('RBAC: no se pudo sincronizar el usuario al iniciar sesion', [
                'usuario_id' => $usuario->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect('/dashboard');
    }
}
