<?php
require_once 'app/libraries/Database.php';

// Mock config constants manually for standalone execution if needed, 
// or just clean PDO usage.
// Let's use raw PDO to be safe and standalone.

$host = 'localhost';
$db   = 'sistema_transportes';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    echo "<h1>Diagnóstico de Asignaciones</h1>";

    // 1. Buscar el bus de la imagen (SAE-9895)
    echo "<h2>1. Buscando bus 'SAE-9895'</h2>";
    $stmt = $pdo->prepare("SELECT * FROM vehiculos WHERE placa LIKE ?");
    $stmt->execute(['%SAE-9895%']);
    $bus = $stmt->fetch();

    if ($bus) {
        echo "<pre>";
        print_r($bus);
        echo "</pre>";
        $busId = $bus['id'];

        // 2. Buscar asignación para este bus
        echo "<h2>2. Buscando asignación en 'asignaciones_buses' para bus_id = $busId</h2>";
        $stmt = $pdo->prepare("SELECT * FROM asignaciones_buses WHERE bus_id = ?");
        $stmt->execute([$busId]);
        $asignaciones = $stmt->fetchAll();

        if ($asignaciones) {
            echo "<pre>";
            print_r($asignaciones);
            echo "</pre>";

            foreach ($asignaciones as $asig) {
                echo "<p>Estado Asignación: " . $asig['estado'] . "</p>";
                echo "<p>Chofer ID: " . var_export($asig['chofer_id'], true) . "</p>";

                if ($asig['chofer_id']) {
                    // Buscar nombre del chofer
                    $stmtUser = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
                    $stmtUser->execute([$asig['chofer_id']]);
                    $user = $stmtUser->fetch();
                    echo "<h3>Datos del Chofer (ID {$asig['chofer_id']}):</h3>";
                    echo "<pre>";
                    print_r($user);
                    echo "</pre>";
                } else {
                    echo "<p style='color:red'>⚠️ El chofer_id es NULL en esta asignación.</p>";
                }
            }
        } else {
            echo "<p style='color:red'>❌ No se encontró ninguna fila en 'asignaciones_buses' para este bus.</p>";
        }
    } else {
        echo "<p style='color:red'>❌ No se encontró el bus con placa SAE-9895 en la tabla 'vehiculos'.</p>";
    }

    // 3. Ver estructura de asignaciones_buses
    echo "<h2>3. Estructura de tabla 'asignaciones_buses'</h2>";
    $stmt = $pdo->query("DESCRIBE asignaciones_buses");
    $columns = $stmt->fetchAll();
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (\PDOException $e) {
    echo "Error DB: " . $e->getMessage();
}
