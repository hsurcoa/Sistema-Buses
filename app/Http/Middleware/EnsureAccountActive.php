<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesion si la cuenta fue desactivada por un administrador mientras
 * el usuario seguia navegando. Equivalente, para rutas Laravel-nativas, al
 * chequeo que `legacy/public/index.php` hace en cada peticion (estado del
 * usuario releido de la BD, ver Fase 2 del plan de migracion).
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->estado !== 'activo') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('mensaje_error', 'Su cuenta está desactivada. Consulte con el administrador.');
        }

        return $next($request);
    }
}
