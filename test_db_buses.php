<?php
// Simular entorno mínimo (ajustar paths según estructura)
// Cargar configuración (asumiendo config.php define DB constants)
require_once 'app/config/config.php';
require_once 'app/libraries/Database.php';

// Instanciar DB
$db = new Database();

// Query manual para probar la consulta del modelo
$tipoBusId = 1; // Asumimos ID 1 para probar
echo "Probando consulta para tipo_bus_id = $tipoBusId...\n";

$db->query("SELECT id, placa, marca, numero_interno FROM buses WHERE tipo_bus_id = :tipo_id AND estado = 1");
$db->bind(':tipo_id', $tipoBusId);
$resultados = $db->resultSet();

echo "Resultados encontrados: " . count($resultados) . "\n";
print_r($resultados);
