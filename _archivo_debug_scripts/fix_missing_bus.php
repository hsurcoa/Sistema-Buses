<?php
// fix_missing_bus.php
// Script to insert a bus for "TURISMO 01" so it shows up in the UI
// Uses RAW PDO to avoid framework dependency issues

header('Content-Type: text/plain');

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sistema_transportes';

echo "=== FIX: AGREGANDO BUS PARA TURISMO 01 (PDO RAW) ===\n\n";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Get ID for "TURISMO 01"
    $tipoNombre = "TURISMO 01";
    $stmt = $pdo->prepare("SELECT id FROM tipos_buses WHERE nombre = :nombre");
    $stmt->execute([':nombre' => $tipoNombre]);
    $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tipo) {
        die("Error: No se encontró el tipo de bus '$tipoNombre'.\n");
    }

    $tipoId = $tipo['id'];
    echo "Tipo de Bus encontrado: $tipoNombre (ID: $tipoId)\n";

    // 2. Check if bus already exists for this type
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM buses WHERE tipo_bus_id = :id");
    $stmt->execute([':id' => $tipoId]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($count['total'] > 0) {
        echo "¡Ya existen buses para este tipo!\n";

        // Update existing buses to 'activo' just in case
        $stmt = $pdo->prepare("UPDATE buses SET estado = 'activo' WHERE tipo_bus_id = :id AND estado != 'activo'");
        $stmt->execute([':id' => $tipoId]);

        if ($stmt->rowCount() > 0) {
            echo "   -> " . $stmt->rowCount() . " bus(es) actualizado(s) a estado 'activo'.\n";
        } else {
            echo "   -> Todos los buses ya están activos.\n";
        }
    } else {
        // 3. Insert new bus
        echo "No hay buses para este tipo. Creando uno nuevo...\n";

        $placa = "TUR-9999";
        $unidad = "T-01";
        $marca = "Volvo Demo";
        $modelo = "2024";
        $estado = "activo"; // Important!

        $sql = "INSERT INTO buses (placa, numero_interno, marca, modelo, tipo_bus_id, estado) 
                VALUES (:placa, :unidad, :marca, :modelo, :tipo_id, :estado)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':placa' => $placa,
            ':unidad' => $unidad,
            ':marca' => $marca,
            ':modelo' => $modelo,
            ':tipo_id' => $tipoId,
            ':estado' => $estado
        ]);

        if ($result) {
            $newBusId = $pdo->lastInsertId();
            echo "✅ Bus creado exitosamente:\n";
            echo "   ID: $newBusId\n";
            echo "   Placa: $placa\n";
            echo "   Unidad: $unidad\n";
            echo "   Tipo: $tipoNombre\n";

            // 4. Create Tripulacion Assignment
            echo "\nCreando asignación de chofer por defecto...\n";
            // Find a valid driver
            $stmt = $pdo->query("SELECT id, nombres FROM usuarios LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $sqlAsign = "INSERT INTO asignaciones_buses (bus_id, chofer_id, estado, fecha_asignacion) VALUES (:bus_id, :chofer_id, 1, NOW())";
                $stmtAsign = $pdo->prepare($sqlAsign);
                if ($stmtAsign->execute([':bus_id' => $newBusId, ':chofer_id' => $user['id']])) {
                    echo "   -> Chofer asignado: " . $user['nombres'] . "\n";
                }
            }
        } else {
            echo "❌ Error al crear el bus.\n";
        }
    }
} catch (PDOException $e) {
    die("Error de Base de Datos: " . $e->getMessage() . "\n");
}

echo "\n\n=== PROCESO COMPLETADO ===\n";
