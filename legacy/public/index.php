<?php
// Cargar el iniciador (bootstrap)
require_once '../app/bootstrap.php';

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
