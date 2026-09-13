<?php
// diagnostico_buses.php
// Script to debug Bus availability issues

require_once 'app/config/config.php';
require_once 'app/libraries/Database.php';

// Instantiate Database
$db = new Database();

header('Content-Type: text/plain');

echo "=== DIAGNOSTICO DE BUSES Y TIPOS ===\n\n";

// 1. Listar Tipos de Buses
echo "1. TABLA: tipos_buses\n";
echo "----------------------\n";
try {
    $db->query("SELECT * FROM tipos_buses");
    $tipos = $db->resultSet();
    if ($tipos) {
        foreach ($tipos as $t) {
            echo "ID: " . $t->id . " | Nombre: " . $t->nombre . " | Capacidad: " . ($t->capacidad ?? 'N/A') . "\n";
        }
    } else {
        echo "No hay tipos de buses registrados or table empty.\n";
    }
} catch (Exception $e) {
    echo "Error querying tipos_buses: " . $e->getMessage() . "\n";
}
echo "\n";

// 2. Listar Buses
echo "2. TABLA: buses\n";
echo "----------------\n";
try {
    $db->query("SELECT id, placa, numero_interno, marca, tipo_bus_id, estado FROM buses");
    $buses = $db->resultSet();
    if ($buses) {
        foreach ($buses as $b) {
            echo "ID: " . $b->id . " | Placa: " . $b->placa . " | Unidad: " . $b->numero_interno . " | TipoID: " . $b->tipo_bus_id . " | Estado: " . $b->estado . " (Type: " . gettype($b->estado) . ")\n";
        }
    } else {
        echo "No hay buses registrados.\n";
    }
} catch (Exception $e) {
    echo "Error querying buses: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Ver Asignaciones
echo "3. TABLA: asignaciones_buses\n";
echo "----------------------------\n";
try {
    $db->query("SELECT * FROM asignaciones_buses");
    $asignaciones = $db->resultSet();
    if ($asignaciones) {
        foreach ($asignaciones as $a) {
            echo "BusID: " . $a->bus_id . " | ChoferID: " . $a->chofer_id . " | Estado: " . $a->estado . "\n";
        }
    } else {
        echo "No hay asignaciones.\n";
    }
} catch (Exception $e) {
    echo "Error querying asignaciones_buses: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL REPORTE ===\n";
