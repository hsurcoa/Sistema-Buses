<?php
// Script de Cierre de Sesión Seguro
session_start();

// 1. Limpiar variables
$_SESSION = array();

// 2. Destruir Cookie (Importante para limpieza total)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destruir sesión
session_destroy();

// 4. Redirigir al Login Elegante
header("Location: login.php");
exit;
