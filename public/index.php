<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Fix: el .htaccess raiz oculta "public/" de la URL visible (para que
// URLROOT siga siendo http://localhost/venta-pasajes sin "/public"), pero
// eso rompe la deteccion automatica del base path de Symfony/Laravel
// cuando la app no vive en la raiz del dominio: REQUEST_URI llega como
// "/venta-pasajes/..." mientras SCRIPT_NAME es
// "/venta-pasajes/public/index.php", y como uno no es prefijo del otro,
// Laravel calcula un base path vacio y interpreta "venta-pasajes" como si
// fuera el primer segmento de la ruta. Se corrige quitando "/public" de
// SCRIPT_NAME antes de que Request::capture() lo lea, tal como hacia el
// propio App.php legacy (que ya hardcodeaba el nombre de carpeta del
// proyecto para el mismo problema).
$_SERVER['SCRIPT_NAME'] = str_replace('/public/index.php', '/index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['PHP_SELF'] = str_replace('/public/index.php', '/index.php', $_SERVER['PHP_SELF'] ?? '');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
