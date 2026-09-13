<?php
// Script de inyección de datos de prueba
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_transportes');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

echo "Iniciando inyección de datos...\n";

// 1. Obtener IDs de usuarios choferes (Intentamos buscar usuarios existentes para no duplicar)
// Buscamos usuarios que parezcan choferes o usamos ids conocidos del dump anterior (19 y 21)
$chofer1_id = 19; // Grover
$chofer2_id = 21; // Robinson

// Verificar si existen, si no, usamos el 1 (Admin) como fallback
$check = $conn->query("SELECT id FROM usuarios WHERE id = $chofer1_id");
if ($check->num_rows == 0) $chofer1_id = 1;

$check = $conn->query("SELECT id FROM usuarios WHERE id = $chofer2_id");
if ($check->num_rows == 0) $chofer2_id = 1;


// 2. Insertar Buses Faltantes (Verificando que no existan por placa)
$buses = [
    ['placa' => 'CAR-001', 'numero' => '101', 'tipo_id' => 6, 'marca' => 'Volvo'],       // Cariñosito 1
    ['placa' => 'CAR-002', 'numero' => '102', 'tipo_id' => 7, 'marca' => 'Scania'],      // Cariñosito 2
    ['placa' => 'MIN-003', 'numero' => '201', 'tipo_id' => 8, 'marca' => 'Toyota'],      // Minibus 3
    ['placa' => 'MIN-004', 'numero' => '202', 'tipo_id' => 9, 'marca' => 'Nissan']       // Minibus 4
];

foreach ($buses as $bus) {
    // Check if exists
    $sql_check = "SELECT id FROM buses WHERE placa = '" . $bus['placa'] . "'";
    $res = $conn->query($sql_check);

    if ($res->num_rows == 0) {
        $sql = "INSERT INTO buses (placa, numero_interno, marca, modelo, plantilla_id, tipo_bus_id, estado) 
                VALUES ('{$bus['placa']}', '{$bus['numero']}', '{$bus['marca']}', '2025', 1, {$bus['tipo_id']}, 'activo')";
        if ($conn->query($sql) === TRUE) {
            $new_bus_id = $conn->insert_id;
            echo "Bus creado: {$bus['placa']} (ID: $new_bus_id)\n";

            // 3. Asignar Chofer Inmediatamente
            // Alternamos choferes
            $chofer_asignar = ($new_bus_id % 2 == 0) ? $chofer1_id : $chofer2_id;

            $sql_assign = "INSERT INTO asignaciones_buses (chofer_id, bus_id, copiloto_id, estado) 
                           VALUES ($chofer_asignar, $new_bus_id, 0, 1)";
            $conn->query($sql_assign);
            echo " -> Chofer asignado (ID: $chofer_asignar)\n";
        } else {
            echo "Error creando bus {$bus['placa']}: " . $conn->error . "\n";
        }
    } else {
        echo "Bus {$bus['placa']} ya existe. Verificando asignación...\n";
        $row = $res->fetch_assoc();
        $bus_id = $row['id'];

        // Verificar si tiene asignación
        $check_assign = $conn->query("SELECT id FROM asignaciones_buses WHERE bus_id = $bus_id AND estado = 1");
        if ($check_assign->num_rows == 0) {
            $chofer_asignar = ($bus_id % 2 == 0) ? $chofer1_id : $chofer2_id;
            $sql_assign = "INSERT INTO asignaciones_buses (chofer_id, bus_id, copiloto_id, estado) 
                           VALUES ($chofer_asignar, $bus_id, 0, 1)";
            $conn->query($sql_assign);
            echo " -> Asignación creada para bus existente.\n";
        }
    }
}

$conn->close();
echo "Proceso completado. Ahora los buses deberían aparecer con conductor.";
