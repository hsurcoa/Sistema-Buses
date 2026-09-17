<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Logout (Fase 2, Tarea 4 — actualizado al eliminar SessionManager).
 *
 * Hasta la Fase 8 este controlador tambien cerraba la sesion nativa de PHP
 * que usaba el puente hacia el legacy (`SessionManager`). Con las 8 fases
 * del roadmap migradas y `SessionManager`/`Sucursal`/`Database` eliminados
 * (ver informe de fin de sesion), el guard de Laravel es la unica fuente de
 * verdad de la sesion: no hay nada mas que cerrar.
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
