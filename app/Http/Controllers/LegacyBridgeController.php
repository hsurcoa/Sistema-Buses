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
    /**
     * Scripts sueltos que Apache servia como archivos reales antes de la
     * migracion (sin pasar por App.php), agrupados por el directorio legacy
     * del que cuelgan. login.php NO esta en esta lista a proposito: ese
     * endpoint ya lo maneja Laravel nativamente (routes/auth.php, Tarea 8).
     * logout.php SI sigue aqui (nadie lo migro: la UI real cierra sesion
     * via /admin/logout, que ya funciona bien pasando por App.php).
     */
    private const LOOSE_SCRIPTS = [
        // ruta tal como llega en $request->path() => carpeta base (relativa a base_path())
        'ajax_info_viaje.php' => 'legacy',
        'ajax_mapa.php' => 'legacy',
        'logout.php' => 'legacy',
        'logout_force.php' => 'legacy',
        // usados por URL directa con el prefijo "public/" (p. ej.
        // URLROOT.'/public/print_ticket.php' desde venta_pasajes.php)
        'public/print_ticket.php' => 'legacy',
        'public/clear_cache.php' => 'legacy',
        'public/diagnostico_caja.php' => 'legacy',
        'public/diagnostico_rutas.php' => 'legacy',
    ];

    public function handle(Request $request): Response
    {
        $path = trim($request->path(), '/');
        if ($path === '/') {
            $path = '';
        }

        if (isset(self::LOOSE_SCRIPTS[$path])) {
            $base = base_path(self::LOOSE_SCRIPTS[$path]);

            return $this->requireScript(dirname($base.'/'.$path), basename($path));
        }

        // Reproduce lo que hacia legacy/public/.htaccess:
        //   RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
        $_GET['url'] = $path;

        return $this->requireScript(base_path('legacy/public'), 'index.php');
    }

    private function requireScript(string $dir, string $script): Response
    {
        $previousCwd = getcwd();
        chdir($dir);

        ob_start();
        try {
            require $dir.'/'.$script;
        } finally {
            $output = ob_get_clean();
            chdir($previousCwd ?: base_path());
        }

        return response($output, http_response_code() ?: 200);
    }
}
