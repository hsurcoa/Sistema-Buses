<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = new Database();

// Check if all current bus_ids in 'viajes' exist in 'vehiculos'
echo "<h1>Integridad de Datos</h1>";
$db->query("SELECT DISTINCT bus_id FROM viajes WHERE bus_id IS NOT NULL");
$busIds = $db->resultSet();
$missing = [];
foreach ($busIds as $b) {
    $id = $b->bus_id;
    $db->query("SELECT id FROM vehiculos WHERE id = :id");
    $db->bind(':id', $id);
    if (!$db->single()) {
        $missing[] = $id;
    }
}

if (count($missing) > 0) {
    echo "<h3 style='color:red'>ADVERTENCIA: Hay IDs de bus en 'viajes' que NO existen en 'vehiculos': " . implode(", ", $missing) . "</h3>";
    echo "Esto bloqueará la creación de la nueva FK. Se recomienda limpiar esos IDs (set to NULL).";
} else {
    echo "<h3 style='color:green'>COMPATIBLE: Todos los bus_id existentes en 'viajes' existen en 'vehiculos'. Es seguro cambiar la FK.</h3>";
}
