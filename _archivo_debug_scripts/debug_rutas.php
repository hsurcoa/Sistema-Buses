<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = new Database();

// 1. Listar Rutas
echo "=== RUTAS ===\n";
$db->query("SELECT id, origen, destino FROM rutas");
$rutas = $db->resultSet();
foreach ($rutas as $r) {
    echo "ID: {$r->id} | {$r->origen} - {$r->destino}\n";
}

// 2. Listar Paradas
echo "\n=== PARADAS ===\n";
$db->query("SELECT * FROM rutas_paradas");
$paradas = $db->resultSet();
if (empty($paradas)) {
    echo "NO HAY PARADAS REGISTRADAS.\n";
} else {
    foreach ($paradas as $p) {
        echo "Ruta ID: {$p->ruta_id} | Parada: {$p->nombre_parada}\n";
    }
}
