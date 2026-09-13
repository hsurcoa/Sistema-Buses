<?php

use App\Http\Controllers\LegacyBridgeController;
use Illuminate\Support\Facades\Route;

// Rutas ya migradas a Laravel van aqui arriba (ninguna todavia en la Fase 1
// mas alla de auth, ver routes/auth.php).
require __DIR__.'/auth.php';

// Todo lo que no coincida con una ruta migrada cae al sistema legacy.
Route::fallback([LegacyBridgeController::class, 'handle']);
