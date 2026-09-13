<?php
// Script de diagnóstico
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_transportes');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

echo "=== TIPOS DE BUSES ===\n";
$res = $conn->query("SELECT id, nombre, capacidad FROM tipos_buses");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Nombre: " . $row['nombre'] . " | Cap: " . $row['capacidad'] . "\n";
}

echo "\n=== FLOTA DE BUSES ACTUAL ===\n";
$res2 = $conn->query("SELECT id, placa, marca, tipo_bus_id FROM buses");
while ($row = $res2->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Placa: " . $row['placa'] . " | Marca: " . $row['marca'] . " | Tipo_Bus_ID Asignado: " . $row['tipo_bus_id'] . "\n";
}

$conn->close();
