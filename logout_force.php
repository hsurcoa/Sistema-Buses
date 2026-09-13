<?php
// Archivo de Emergencia para limpiar navegador
session_start();

// 1. Vaciar variables
$_SESSION = array();

// 2. Destruir Cookie de Sesión (Crucial para romper bucles persistentes)
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

// 3. Destruir Sesión
session_destroy();

echo "<h1>Sesión Destruida Correctamente</h1>";
echo "<p>El navegador está limpio. <a href='login.php'>Haz clic aquí para volver a intentar entrar</a>.</p>";
