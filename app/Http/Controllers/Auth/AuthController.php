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
 * Login/logout migrados a Laravel (Fase 1, Tarea 8).
 *
 * Puente de sesion (Tarea 6): el motor de sesiones de Laravel es
 * independiente de la sesion nativa de PHP que usa el legacy
 * (SessionManager -> session_start()/$_SESSION). Para que ambos lados vean
 * al mismo usuario logueado, este controller NO usa el guard de sesion de
 * Laravel como fuente de verdad: abre la MISMA sesion nativa que abre el
 * legacy (misma clase SessionManager, mismo nombre de cookie) y escribe en
 * ella exactamente lo mismo que escribia legacy/login.php
 * (user_id/usuario/email/rol). Adicionalmente llama Auth::login() para que
 * rutas Laravel-nativas futuras puedan usar el guard estandar si lo
 * necesitan; eso vive en su propia cookie/sesion independiente y no
 * reemplaza al puente.
 */
class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        $session = $this->legacySession();

        if ($session->isAuthenticated()) {
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

        $session = $this->legacySession();
        $session->regenerateId();
        $session->setUserData([
            'user_id' => $usuario->id,
            'usuario' => $usuario->nombreCompleto(),
            'email' => $usuario->email,
            'rol' => $usuario->rol?->nombre ?? 'usuario',
        ]);

        // Guard nativo de Laravel, para rutas ya migradas que quieran usar
        // Auth::user()/auth middleware mas adelante (sesion propia, no
        // interfiere con el puente de arriba).
        Auth::login($usuario);

        // RBAC spatie al dia con lo que el admin haya cambiado en el modulo
        // legacy (Tarea 9). Todavia ninguna ruta depende de spatie, asi que un
        // fallo aqui se registra pero no impide entrar a vender.
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

    /**
     * Abre (o reanuda) la misma sesion nativa de PHP que usa el legacy,
     * reutilizando su propia clase para no duplicar la logica de cookie/
     * lifetime/activity-timeout en dos sitios.
     */
    private function legacySession(): \SessionManager
    {
        require_once base_path('legacy/app/config/config.php');
        require_once base_path('legacy/app/core/SessionManager.php');

        return \SessionManager::getInstance();
    }
}
