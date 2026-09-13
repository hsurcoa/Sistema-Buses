<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Puente hacia el sistema legacy (patron strangler fig).
 *
 * Unico componente que "sabe" del sistema viejo: toma cualquier request que
 * no coincida con una ruta ya migrada a Laravel, la traduce exactamente al
 * formato que esperaba legacy/public/.htaccess (index.php?url=...), y deja
 * que legacy/app/core/App.php la despache tal cual lo hace hoy (mismo
 * controlador/modelo/vista legacy, sin cambios).
 *
 * Ver docs/superpowers/specs/2026-09-13-migracion-laravel-design.md (3.2)
 * y docs/superpowers/plans/2026-09-13-migracion-laravel-fase1-plan.md (Tarea 7).
 *
 * Notas de implementacion:
 * - Se hace `require` (no un subproceso) para compartir sesion y conexion a
 *   BD dentro del mismo request de Laravel.
 * - Si el codigo legacy llama header()/exit() (p. ej. redirects de login),
 *   esas llamadas actuan directo sobre la respuesta HTTP real del proceso
 *   PHP, saltandose el ciclo de vida normal de Laravel (terminate
 *   middleware, etc.). Es una limitacion conocida y aceptada mientras el
 *   bridge siga activo; se resuelve modulo por modulo a medida que cada
 *   controlador legacy se reemplaza por uno de Laravel (roadmap, seccion 5).
 * - Este controller se "apaga" solo cuando ya no queda ningun modulo legacy
 *   por servir (ultimo paso del roadmap); hasta entonces es el fallback de
 *   TODO lo que Laravel no reconozca todavia.
 */
class LegacyBridgeController extends Controller
{
    public function handle(Request $request): Response
    {
        $legacyPublic = base_path('legacy/public');

        // Reproduce lo que hacia legacy/public/.htaccess:
        //   RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
        $_GET['url'] = trim($request->path(), '/');
        if ($request->path() === '/') {
            $_GET['url'] = '';
        }

        $previousCwd = getcwd();
        chdir($legacyPublic);

        ob_start();
        try {
            require $legacyPublic.'/index.php';
        } finally {
            $output = ob_get_clean();
            chdir($previousCwd ?: base_path());
        }

        return response($output, http_response_code() ?: 200);
    }
}
