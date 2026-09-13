<?php
// Simple script to check open sessions in the database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_transportes');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Diagnóstico de Cajas Abiertas</h1>";

$sql = "SELECT cs.id, cs.usuario_id, u.nombres, cs.fecha_apertura, cs.monto_inicial, cs.estado 
        FROM cajas_sesiones cs 
        JOIN usuarios u ON cs.usuario_id = u.id 
        WHERE cs.estado = 'ABIERTA'";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>ID Sesion</th><th>Usuario ID</th><th>Nombre</th><th>Fecha Apertura</th><th>Estado</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td>" . $row["usuario_id"] . "</td>";
        echo "<td>" . $row["nombres"] . "</td>";
        echo "<td>" . $row["fecha_apertura"] . "</td>";
        echo "<td>" . $row["estado"] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No hay cajas abiertas.";
}

$conn->close();
