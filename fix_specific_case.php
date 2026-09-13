<?php
// Script de corrección Específica: Caso Bus his-1988 y Chofer Diego Coaquira
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_transportes');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

echo "Iniciando corrección de datos específica...\n";

// 1. Obtener ID del Chofer Diego Coaquira
$res_chofer = $conn->query("SELECT id FROM usuarios WHERE nombres LIKE '%Diego%' AND apellidos LIKE '%Coaquira%' LIMIT 1");
if ($res_chofer->num_rows > 0) {
    $chofer_row = $res_chofer->fetch_assoc();
    $chofer_id = $chofer_row['id'];
    echo "Chofer 'Diego Coaquira' encontrado con ID: $chofer_id\n";
} else {
    // Si no existe, usamos uno por defecto para no romper el script
    $chofer_id = 4; // ID supuesto de Martin Diego Coaquira del dump anterior
    echo "Chofer no encontrado por nombre exacto. Usando ID fallback: $chofer_id\n";
}

// 2. Gestionar el Bus 'his-1988' para el Tipo 'MINIBUS CARIÑOSITO 3' (ID 8)
$placa_bus = 'his-1988';
$tipo_bus_id = 8; // ID de Minibus Cariñosito 3

$res_bus = $conn->query("SELECT id FROM buses WHERE placa = '$placa_bus'");
$bus_id = 0;

if ($res_bus->num_rows > 0) {
    // El bus existe, actualizamos su tipo
    $bus_row = $res_bus->fetch_assoc();
    $bus_id = $bus_row['id'];

    $conn->query("UPDATE buses SET tipo_bus_id = $tipo_bus_id WHERE id = $bus_id");
    echo "Bus '$placa_bus' existente actualizado al Tipo ID: $tipo_bus_id\n";
} else {
    // El bus no existe, lo creamos
    $sql_insert = "INSERT INTO buses (placa, numero_interno, marca, modelo, plantilla_id, tipo_bus_id, estado) 
                   VALUES ('$placa_bus', '305', 'Toyota', 'Hiace', 1, $tipo_bus_id, 'activo')";
    if ($conn->query($sql_insert) === TRUE) {
        $bus_id = $conn->insert_id;
        echo "Bus '$placa_bus' creado exitosamente con ID: $bus_id\n";
    } else {
        die("Error al crear bus: " . $conn->error);
    }
}

// 3. Crear Asignación Dinámica
// Primero limpiamos asignaciones previas de este bus para evitar conflictos
$conn->query("UPDATE asignaciones_buses SET estado = 0 WHERE bus_id = $bus_id");

// Insertar nueva asignación activa
// Necesitamos un copiloto ID valido (ID 20 es Alan Vera)
$copiloto_id = 20;

$sql_assign = "INSERT INTO asignaciones_buses (chofer_id, bus_id, copiloto_id, estado) 
               VALUES ($chofer_id, $bus_id, $copiloto_id, 1)";

if ($conn->query($sql_assign) === TRUE) {
    echo "Asignación exitosa: Bus '$placa_bus' vinculado a Chofer ID $chofer_id.\n";
} else {
    echo "Error en asignación: " . $conn->error . "\n";
}

$conn->close();
echo "Corrección finalizada. Puede probar el formulario.";
