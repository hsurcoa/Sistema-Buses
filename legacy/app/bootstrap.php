<?php
// Produccion: los errores de PHP van al log de Apache, no a la pantalla.
// (php.ini tiene display_errors=On y output_buffering=Off: un Notice rompia las
// respuestas JSON de varios endpoints y ademas exponia rutas del servidor.)
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Cargar configuraciones
require_once 'config/config.php';

// Autoload Core Libraries
spl_autoload_register(function ($className) {
    if (file_exists('../app/core/' . $className . '.php')) {
        require_once 'core/' . $className . '.php';
    }
});

// ✅ SOLUCIÓN: Inicializar SessionManager de forma centralizada
// Esto garantiza que TODAS las peticiones tengan sesión activa
require_once 'core/SessionManager.php';
$sessionManager = SessionManager::getInstance();
