<?php
// Cargar el iniciador (bootstrap)
require_once '../app/bootstrap.php';

// Cuenta desactivada por un administrador: la sesion abierta deja de valer.
// Rol y sucursal se releen de la BD para que un cambio aplique sin re-login.
if ($sessionManager->isAuthenticated()) {
    $dbSesion = new Database();
    $dbSesion->query("SELECT u.estado, r.nombre AS rol, u.sucursal_id, t.nombre_sede AS sucursal_nombre
                      FROM usuarios u
                      LEFT JOIN roles r ON r.id = u.rol_id
                      LEFT JOIN terminales t ON t.id = u.sucursal_id
                      WHERE u.id = :id");
    $dbSesion->bind(':id', $sessionManager->getUserId());
    $cuenta = $dbSesion->single();
    if (!$cuenta || $cuenta->estado !== 'activo') {
        $sessionManager->destroy();
    } else {
        // Rol y sucursal siempre al dia (un cambio del administrador aplica sin re-login)
        $_SESSION['rol'] = $cuenta->rol ?: ($_SESSION['rol'] ?? null);
        $_SESSION['sucursal_id'] = $cuenta->sucursal_id ? (int) $cuenta->sucursal_id : null;
        $_SESSION['sucursal_nombre'] = $cuenta->sucursal_nombre;
    }
}

// Todo el sistema es de uso interno: sin sesion no se sirve ninguna pantalla
// ni endpoint. Antes varias pantallas (dashboard, venta de pasajes, admin...)
// se mostraban como "Invitado" y el error recien aparecia al intentar vender
// ("Sesion expirada"). El login vive en Laravel (/login.php) y los scripts
// sueltos (ajax_mapa.php, print_ticket.php...) no pasan por este archivo.
if (!$sessionManager->isAuthenticated()) {
    $esAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
        || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET';

    if ($esAjax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'success' => false,
            'msg' => 'Su sesión expiró. Inicie sesión nuevamente.',
            'message' => 'Su sesión expiró. Inicie sesión nuevamente.',
            'code' => 'SESSION_EXPIRED',
            'redirect' => URLROOT . '/login.php',
        ]);
        exit;
    }

    header('Location: ' . URLROOT . '/login.php');
    exit;
}

// Iniciar la Core Library
$init = new App();
