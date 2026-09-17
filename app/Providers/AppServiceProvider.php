<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Constantes URLROOT/SITENAME, heredadas del nombrado legacy y usadas
        // en ~35 vistas Blade portadas (Fases 2-8) y varios controladores.
        // Antes venian de legacy/app/config/config.php (require_once); ahora
        // se derivan de la config nativa de Laravel (.env: APP_URL/APP_NAME),
        // fuente unica de verdad — ver informe de fin de sesion (eliminacion
        // de PHP puro).
        if (! defined('URLROOT')) {
            define('URLROOT', rtrim(config('app.url'), '/'));
        }
        if (! defined('SITENAME')) {
            define('SITENAME', config('app.name'));
        }
    }
}
