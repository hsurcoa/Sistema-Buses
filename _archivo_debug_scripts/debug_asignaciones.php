<?php
// Script de Diagnóstico de Asignaciones
// Uso: php debug_asignaciones.php [ID_VIAJE]
// O abrir en navegador: http://localhost/venta-pasajes/debug_asignaciones.php?id=12

define('APPROOT', __DIR__ . '/app');
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

// Obtener ID (CLI o GET)
$viajeId = isset($_GET['id']) ? $_GET['id'] : (isset($argv[1]) ? $argv[1] : 12);

echo "<h1>🔍 Diagnóstico de Tripulación - Viaje #$viajeId</h1>";
echo "<pre>";

$db = new Database();

// 1. Verificar Datos del Viaje
echo "<h3>1. Datos del Viaje (Tabla 'viajes')</h3>";
$db->query("SELECT id, ruta_id, bus_id, chofer_id, fecha_salida, hora_salida FROM viajes WHERE id = :id");
$db->bind(':id', $viajeId);
$viaje = $db->single();

if (!$viaje) {
    die("❌ El viaje ID $viajeId no existe.\n");
}

print_r($viaje);

if (!$viaje->bus_id) {
    die("❌ ALERTA: Este viaje NO tiene un Bus ID asignado en la columna 'bus_id'.\n");
}
echo "✅ Bus ID asignado en viaje: " . $viaje->bus_id . "\n";


// 2. Verificar Datos del Vehículo
echo "\n<h3>2. Datos del Bus (Tabla 'vehiculos')</h3>";
$db->query("SELECT id, placa, marca, estado FROM vehiculos WHERE id = :id");
$db->bind(':id', $viaje->bus_id);
$bus = $db->single();

if (!$bus) {
    echo "❌ El Bus ID {$viaje->bus_id} NO existe en la tabla 'vehiculos'.\n";
} else {
    print_r($bus);
    echo "✅ Placa del Bus: " . ($bus->placa ?? 'Sin Placa') . "\n";
}


// 3. Buscar Asignaciones (Todas, sin filtrar por estado)
echo "\n<h3>3. Asignaciones de Tripulación (Tabla 'asignaciones_buses')</h3>";
echo "Buscando asignaciones para Bus ID: {$viaje->bus_id}...\n";

$db->query("SELECT * FROM asignaciones_buses WHERE bus_id = :bus_id");
$db->bind(':bus_id', $viaje->bus_id);
$asignaciones = $db->resultSet();

if (empty($asignaciones)) {
    echo "❌ NO existen registros en 'asignaciones_buses' para este bus.\n";
    echo "--> CAUSA PROBABLE: No se ha asignado tripulación a este bus en el módulo de Flota.\n";
} else {
    echo "Encontradas " . count($asignaciones) . " asignaciones:\n";
    foreach ($asignaciones as $asig) {
        echo "------------------------------------------------\n";
        echo "ID Asignación: " . $asig->id . "\n";
        echo "Estado: " . $asig->estado . " " . ($asig->estado == 1 ? "(ACTIVO)" : "(INACTIVO/HISTÓRICO)") . "\n";
        echo "Chofer ID: " . ($asig->chofer_id ?? 'NULL') . "\n";
        echo "Copiloto ID: " . ($asig->copiloto_id ?? 'NULL') . "\n";

        // Verificar Chofer
        if ($asig->chofer_id) {
            $db->query("SELECT id, nombres, apellidos, estado FROM usuarios WHERE id = :id");
            $db->bind(':id', $asig->chofer_id);
            $chofer = $db->single();
            if ($chofer) {
                echo "  -> Chofer: {$chofer->nombres} {$chofer->apellidos} (Estado User: {$chofer->estado})\n";
            } else {
                echo "  -> ❌ Chofer ID {$asig->chofer_id} NO existe en tabla 'usuarios'.\n";
            }
        }

        // Verificar Copiloto
        if ($asig->copiloto_id) {
            $db->query("SELECT id, nombres, apellidos, estado FROM usuarios WHERE id = :id");
            $db->bind(':id', $asig->copiloto_id);
            $copiloto = $db->single();
            if ($copiloto) {
                echo "  -> Copiloto: {$copiloto->nombres} {$copiloto->apellidos} (Estado User: {$copiloto->estado})\n";
            } else {
                echo "  -> ❌ Copiloto ID {$asig->copiloto_id} NO existe en tabla 'usuarios'.\n";
            }
        }
    }
}

echo "</pre>";
