<?php
// ajax_mapa.php
header('Content-Type: application/json');

// 1. Configuración de Base de Datos
$host = 'localhost';
$db   = 'venta_pasajes'; // Corrected from venta_pasajes
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
} catch (\PDOException $e) {
    echo json_encode(['error' => 'Error de conexión DB: ' . $e->getMessage()]);
    exit;
}

// 2. Validar Input
if (!isset($_POST['id_viaje']) || empty($_POST['id_viaje'])) {
    echo json_encode(['error' => 'ID de viaje no proporcionado']);
    exit;
}

$idViaje = (int)$_POST['id_viaje'];
$response = [];

try {
    // A. OBTENER INFO DEL VIAJE Y BUS
    // Unimos tablas viajes, rutas y buses
    $sqlInfo = "SELECT 
                    v.id, 
                    v.hora_salida, 
                    r.origen, 
                    r.destino, 
                    r.precio_base,
                    b.capacidad,
                    b.placa,
                    b.columnas, 
                    b.pisos
                FROM viajes v
                JOIN rutas r ON v.ruta_id = r.id
                JOIN buses b ON v.bus_id = b.id
                WHERE v.id = ?";

    $stmt = $pdo->prepare($sqlInfo);
    $stmt->execute([$idViaje]);
    $infoViaje = $stmt->fetch();

    if (!$infoViaje) {
        throw new Exception("Viaje no encontrado");
    }

    $response['info'] = [
        'precio' => $infoViaje['precio_base'],
        'origen' => $infoViaje['origen'],
        'destino' => $infoViaje['destino'],
        'hora' => $infoViaje['hora_salida'],
        'placa' => $infoViaje['placa']
    ];

    $response['bus'] = [
        'asientos' => $infoViaje['capacidad'],
        'columnas' => $infoViaje['columnas'] ?? 4,
        'pisos' => $infoViaje['pisos'] ?? 1
    ];

    // B. OBTENER ASIENTOS OCUPADOS
    // Consultamos la tabla boletos para este viaje
    $sqlOcupados = "SELECT numero_asiento, estado FROM boletos 
                    WHERE viaje_id = ? AND estado IN ('vendido', 'reservado')";

    $stmtOcc = $pdo->prepare($sqlOcupados);
    $stmtOcc->execute([$idViaje]);

    $ocupados = [];
    while ($row = $stmtOcc->fetch()) {
        // Guardamos objeto: { "5": "vendido" }
        $ocupados[$row['numero_asiento']] = $row['estado'];
    }

    $response['ocupados'] = $ocupados;
    $response['success'] = true;
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
