<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

/**
 * Las ~60 vistas portadas del legacy (Fases 2-8) envian el token CSRF en un
 * campo `csrf_token` (no `_token`, el nombre que espera Laravel de forma
 * nativa) — heredado de como el legacy generaba sus formularios/AJAX. En vez
 * de tocar cada uno de esos call sites, se acepta tambien ese nombre de
 * campo aqui.
 *
 * Sin este alias, el middleware CSRF nativo de Laravel (ya activo por
 * defecto en el grupo `web`) rechazaria con 419 todas las peticiones POST
 * migradas a Laravel antes de que lleguen al controlador: el bug estaba
 * activo en produccion (verificado que ningun test lo detecto porque
 * `VerifyCsrfToken::runningUnitTests()` desactiva la verificacion bajo
 * `php artisan test`).
 */
class VerifyCsrfToken extends Middleware
{
    protected function getTokenFromRequest($request): ?string
    {
        return parent::getTokenFromRequest($request) ?: $request->input('csrf_token');
    }
}
