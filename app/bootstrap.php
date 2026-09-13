<?php
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
