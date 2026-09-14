<?php

use App\Http\Controllers\LegacyBridgeController;
use Illuminate\Support\Facades\Route;

// Catch-all hacia el sistema legacy (cualquier metodo HTTP: los formularios y
// el AJAX de ventas/caja son POST). Se registra DESPUES de routes/web.php y
// fuera del grupo `web` (ver bootstrap/app.php): sin sesion, CSRF ni cifrado
// de cookies de Laravel, porque el legacy maneja los suyos.
Route::any('{legacyPath?}', [LegacyBridgeController::class, 'handle'])
    ->where('legacyPath', '.*');
