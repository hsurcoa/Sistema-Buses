<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = new Database();

echo "<h1>Aplicando Corrección de Base de Datos...</h1>";

try {
    // 1. Drop old FK
    echo "Intentando eliminar FK antigua (viajes_ibfk_2)...<br>";
    $db->query("ALTER TABLE viajes DROP FOREIGN KEY viajes_ibfk_2");
    if ($db->execute()) {
        echo "<span style='color:green'>Correcto: FK antigua eliminada.</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:orange'>Nota: " . $e->getMessage() . " (Puede que ya no exista)</span><br>";
}

try {
    // 2. Add new FK
    echo "Intentando agregar nueva FK hacia 'vehiculos'...<br>";
    $db->query("ALTER TABLE viajes ADD CONSTRAINT viajes_ibfk_2 FOREIGN KEY (bus_id) REFERENCES vehiculos(id)");
    if ($db->execute()) {
        echo "<span style='color:green'>ÉXITO: Nueva referencia a 'vehiculos' creada.</span><br>";
    } else {
        echo "<span style='color:red'>ERROR: No se pudo crear la nueva FK.</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>EXCEPCIÓN CRÍTICA: " . $e->getMessage() . "</span><br>";
}

echo "<h2>Verificación Final</h2>";
$db->query("SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = 'viajes' AND COLUMN_NAME = 'bus_id'");
$res = $db->resultSet();
foreach ($res as $r) {
    echo "Bus ID ahora referencia a: <b>" . $r->REFERENCED_TABLE_NAME . "</b> (Debería ser 'vehiculos')<br>";
}
