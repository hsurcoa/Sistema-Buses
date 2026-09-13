<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=sistema_transportes;charset=utf8mb4", "root", "");
    $stmt = $pdo->query("DESCRIBE vehiculos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "COLUMNAS DE VEHICULOS:\n";
    foreach($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
