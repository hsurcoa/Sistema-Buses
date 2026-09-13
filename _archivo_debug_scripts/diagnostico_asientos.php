<?php
// Script de diagnóstico para verificar el estado de la base de datos
require_once 'app/config/config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== DIAGNÓSTICO DEL SISTEMA ===\n\n";

    // 1. Verificar viajes
    echo "1. VIAJES PROGRAMADOS:\n";
    $stmt = $pdo->query("SELECT id, ruta_id, fecha_salida, hora_salida, estado FROM viajes WHERE id IN (1,2) ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Viaje #{$row['id']}: Ruta {$row['ruta_id']}, {$row['fecha_salida']} {$row['hora_salida']}, Estado: {$row['estado']}\n";
    }

    // 2. Verificar boletos del viaje 1
    echo "\n2. BOLETOS DEL VIAJE #1:\n";
    $stmt = $pdo->query("SELECT id, numero_asiento, estado, codigo_boleto FROM boletos WHERE viaje_id = 1 ORDER BY numero_asiento");
    $boletos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($boletos) > 0) {
        foreach ($boletos as $b) {
            echo "   Asiento {$b['numero_asiento']}: {$b['estado']} (Código: {$b['codigo_boleto']})\n";
        }
    } else {
        echo "   No hay boletos registrados para este viaje\n";
    }

    // 3. Verificar boletos del viaje 2
    echo "\n3. BOLETOS DEL VIAJE #2:\n";
    $stmt = $pdo->query("SELECT id, numero_asiento, estado, codigo_boleto FROM boletos WHERE viaje_id = 2 ORDER BY numero_asiento");
    $boletos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($boletos) > 0) {
        foreach ($boletos as $b) {
            echo "   Asiento {$b['numero_asiento']}: {$b['estado']} (Código: {$b['codigo_boleto']})\n";
        }
    } else {
        echo "   No hay boletos registrados para este viaje\n";
    }

    // 4. Verificar si el asiento 28 está ocupado en algún viaje
    echo "\n4. VERIFICAR ASIENTO 28 EN TODOS LOS VIAJES:\n";
    $stmt = $pdo->query("SELECT viaje_id, numero_asiento, estado FROM boletos WHERE numero_asiento = 28");
    $asientos28 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($asientos28) > 0) {
        foreach ($asientos28 as $a) {
            echo "   Viaje #{$a['viaje_id']}: Asiento {$a['numero_asiento']} - {$a['estado']}\n";
        }
    } else {
        echo "   El asiento 28 NO está ocupado en ningún viaje\n";
    }

    // 5. Verificar estructura de la tabla boletos
    echo "\n5. ESTRUCTURA DE LA TABLA BOLETOS:\n";
    $stmt = $pdo->query("DESCRIBE boletos");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['Field']}: {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
