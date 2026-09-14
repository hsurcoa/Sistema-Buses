<?php

// Configuracion del puente hacia el sistema legacy (LegacyBridgeController).
return [
    // Base interna para llegar a /__legacy. Vacio = http://127.0.0.1[:puerto]/<base>
    // calculado del request (el .htaccess raiz solo acepta /__legacy por loopback).
    'internal_url' => env('LEGACY_BRIDGE_URL'),

    // Segundos maximos que se espera al legacy (reportes/backups lentos).
    'timeout' => (int) env('LEGACY_BRIDGE_TIMEOUT', 300),
];
