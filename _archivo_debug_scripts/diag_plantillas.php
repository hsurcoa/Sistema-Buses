<?php
// diag_plantillas.php
header('Content-Type: text/plain');
$dsn = "mysql:host=localhost;dbname=sistema_transportes;charset=utf8";
$pdo = new PDO($dsn, 'root', '');
$stmt = $pdo->query("SELECT id, nombre, descripcion FROM plantillas_bus LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ID: " . $r['id'] . " | Nombre: " . $r['nombre'] . "\n";
}
