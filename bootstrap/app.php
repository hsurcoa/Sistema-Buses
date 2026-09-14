<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // El puente al legacy va al final y sin middleware `web`.
        then: function () {
            Route::group([], base_path('routes/legacy.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Formulario de login abierto mas tiempo que la sesion de Laravel: en
        // vez de la pagina "419 Page Expired", volver al login con un aviso.
        // (Laravel ya lo convirtio a HttpException 419 antes de estos callbacks.)
        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $e->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            return redirect('/login.php')
                ->withInput($request->only('email'))
                ->with('mensaje_error', 'La página expiró. Vuelva a ingresar sus credenciales.');
        });
    })->create();
