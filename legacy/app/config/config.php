<?php
// Configuración de acceso a la Base de Datos
//
// Migración a Laravel (Fase 1, Tarea 4): estos valores ahora se leen del
// mismo .env que usa Laravel (raíz del proyecto) para no duplicar
// credenciales entre los dos lados. Se mantiene un fallback a los valores
// originales por si este archivo se ejecuta standalone (fuera del bridge
// de Laravel, sin que .env haya sido cargado todavía) — p. ej. durante la
// ventana de transición de la Fase 1 en la que legacy/public/index.php
// se sigue sirviendo directo. El fallback se retira cuando el
// LegacyBridgeController sea el único punto de entrada (Tarea 7).
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USERNAME') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_DATABASE') ?: 'sistema_transportes');

// Rutas de la Aplicación
define('APPROOT', dirname(dirname(__FILE__)));
define('URLROOT', 'http://localhost/venta-pasajes');
define('SITENAME', 'Sistema Venta Pasajes');
