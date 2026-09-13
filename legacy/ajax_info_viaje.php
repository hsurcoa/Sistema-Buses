<?php
// ajax_info_viaje.php
include 'conexion.php'; // Usa la conexión centralizada $pdo

header('Content-Type: application/json');

if (empty($_POST['id_viaje'])) {
    echo json_encode(['success' => false, 'error' => 'ID de viaje no proporcionado']);
    exit;
}

$idViaje = (int)$_POST['id_viaje'];

try {
    // 1. Obtener Info del Viaje, Ruta y Bus
    $stmt = $pdo->prepare("
        SELECT 
            v.hora_salida,
            r.origen, r.destino, v.precio_base,
            b.placa, b.capacidad, b.columnas
        FROM viajes v
        JOIN rutas r ON v.ruta_id = r.id
        JOIN buses b ON v.bus_id = b.id
        WHERE v.id = ?
    ");
    $stmt->execute([$idViaje]);
    $row = $stmt->fetch();

    if (!$row) {
        throw new Exception("Viaje no encontrado");
    }

    // 2. Obtener Asientos Ocupados y su Estado
    $stmtOcc = $pdo->prepare("
        SELECT numero_asiento, estado 
        FROM boletos 
        WHERE viaje_id = ? AND estado != 'anulado'
    ");
    $stmtOcc->execute([$idViaje]);

    // Mapear: asiento => estado
    $ocupados = [];
    while ($ticket = $stmtOcc->fetch()) {
        $ocupados[$ticket['numero_asiento']] = $ticket['estado'];
    }

    // 3. Estructurar Respuesta JSON para el Frontend
    $response = [
        'success' => true,
        'info' => [
            'precio'  => (float)$row['precio_base'],
            'origen'  => $row['origen'],
            'destino' => $row['destino'],
            'hora'    => date('H:i', strtotime($row['hora_salida'])),
            'placa'   => $row['placa'] ?? 'NO-ASIGNADO'
        ],
        'bus' => [
            'asientos' => (int)$row['capacidad'],
            'columnas' => (int)$row['columnas']
        ],
        'ocupados' => $ocupados // Objeto: { "5": "vendido", "10": "reservado" }
    ];

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
