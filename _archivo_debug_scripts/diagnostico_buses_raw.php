<?php
// diagnostico_buses_raw.php
// Raw PDO connection to debug DB

header('Content-Type: text/plain');

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sistema_transportes';

echo "=== DIAGNOSTICO DE BUSES Y TIPOS (RAW PDO) ===\n\n";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión Exitosa.\n\n";

    // 1. Listar Tipos de Buses
    echo "1. TABLA: tipos_buses\n";
    echo "----------------------\n";
    $stmt = $pdo->query("SELECT * FROM tipos_buses");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($tipos) {
        foreach ($tipos as $t) {
            echo "ID: " . $t['id'] . " | Nombre: " . $t['nombre'] . "\n";
        }
    } else {
        echo "No hay tipos de buses registrados.\n";
    }
    echo "\n";

    // 2. Listar Buses
    echo "2. TABLA: buses\n";
    echo "----------------\n";
    $stmt = $pdo->query("SELECT id, placa, numero_interno, tipo_bus_id, estado FROM buses");
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($buses) {
        foreach ($buses as $b) {
            echo "ID: " . $b['id'] . " | Placa: " . $b['placa'] . " | Unidad: " . $b['numero_interno'] . " | TipoID: " . $b['tipo_bus_id'] . " | Estado: " . $b['estado'] . " (Type: " . gettype($b['estado']) . ")\n";
        }
    } else {
        echo "No hay buses registrados.\n";
    }
    echo "\n";

    // 3. Ver Asignaciones
    echo "3. TABLA: asignaciones_buses\n";
    echo "----------------------------\n";
    $stmt = $pdo->query("SELECT * FROM asignaciones_buses");
    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($asignaciones) {
        foreach ($asignaciones as $a) {
            echo "BusID: " . $a['bus_id'] . " | ChoferID: " . $a['chofer_id'] . " | Estado: " . $a['estado'] . "\n";
        }
    } else {
        echo "No hay asignaciones.\n";
    }
} catch (PDOException $e) {
    echo "Error de Conexión o Consulta: " . $e->getMessage() . "\n";
}

// 4. Ver Rutas
echo "\n4. TABLA: rutas\n";
echo "---------------\n";
try {
    $stmt = $pdo->query("SELECT id, origen, destino, estado FROM rutas");
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rutas) {
        foreach ($rutas as $r) {
            echo "ID: " . $r['id'] . " | Ruta: " . $r['origen'] . " - " . $r['destino'] . " | Estado: " . $r['estado'] . " (Type: " . gettype($r['estado']) . ")\n";
        }
    } else {
        echo "No hay rutas.\n";
    }
} catch (Exception $e) {
    echo "Error querying rutas: " . $e->getMessage() . "\n";
}

// 5. Ver Vehiculos
echo "\n5. TABLA: vehiculos\n";
echo "-------------------\n";
try {
    $stmt = $pdo->query("SELECT id, placa, estado FROM vehiculos");
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($vehiculos) {
        foreach ($vehiculos as $v) {
            echo "ID: " . $v['id'] . " | Placa: " . $v['placa'] . " | Estado: " . $v['estado'] . "\n";
        }
    } else {
        echo "No hay vehiculos.\n";
    }
} catch (Exception $e) {
    echo "Error querying vehiculos: " . $e->getMessage() . "\n";
}
