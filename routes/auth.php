<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Ruta primaria: coincide con la que el legacy tiene hardcodeada en todos
// sus redirects (URLROOT.'/login.php' en SessionManager::requireAuth() y
// en varios controladores). Debe conservar el nombre exacto "login.php"
// aunque ya no sea un archivo fisico.
Route::get('login.php', [AuthController::class, 'showLogin'])->name('login');
Route::post('login.php', [AuthController::class, 'login']);

// Alias amigable para accesos nuevos (no usado por el legacy todavia).
Route::get('login', [AuthController::class, 'showLogin']);
Route::post('login', [AuthController::class, 'login']);
