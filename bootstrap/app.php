<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Cierre de la migracion (roadmap, paso 9): las 8 fases de negocio y el
// puente hacia legacy/ (patron strangler fig, ver
// docs/superpowers/specs/2026-09-13-migracion-laravel-design.md) ya no
// existen — todo el trafico real pasa por routes/web.php. Ver
// docs/superpowers/plans/2026-09-17-eliminacion-php-puro.md.
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureAccountActive::class,
        ]);
        // Acepta tambien el campo `csrf_token` (heredado de las vistas
        // portadas del legacy) ademas del `_token`/`X-CSRF-TOKEN` nativos de
        // Laravel. Ver App\Http\Middleware\VerifyCsrfToken.
        $middleware->replaceInGroup(
            'web',
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Formulario de login abierto mas tiempo que la sesion de Laravel: en
        // vez de la pagina "419 Page Expired", volver al login con un aviso.
        // (Laravel ya lo convirtio a HttpException 419 antes de estos callbacks.)
        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $e->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            // Las vistas migradas (Fases 2-8) ya escuchan 401 + code
            // SESSION_EXPIRED para AJAX (ver resources/views/layouts/app.blade.php).
            // Mismo aviso para una sesion/CSRF vencida en una llamada AJAX, en
            // vez de que el JS reciba una redireccion que no espera.
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Sesión expirada. Por favor, recargue la página e inicie sesión nuevamente.',
                    'code' => 'SESSION_EXPIRED',
                    'redirect' => '/login.php',
                ], 401);
            }

            return redirect('/login.php')
                ->withInput($request->only('email'))
                ->with('mensaje_error', 'La página expiró. Vuelva a ingresar sus credenciales.');
        });
    })->create();
