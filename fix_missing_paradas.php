<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = new Database();

echo "Generando paradas para rutas sin configuración...\n";

// 1. Obtener todas las rutas
$db->query("SELECT id, destino FROM rutas");
$rutas = $db->resultSet();

foreach ($rutas as $r) {
    // Verificar si ya tiene paradas
    $db->query("SELECT COUNT(*) as total FROM rutas_paradas WHERE ruta_id = :rid");
    $db->bind(':rid', $r->id);
    $res = $db->single();

    if ($res->total == 0) {
        echo "Agregando paradas para Ruta {$r->id} ({$r->destino})...\n";

        // Paradas genéricas basadas en la ruta norte (Lago Titicaca)
        $paradasComunes = [
            ['Batallas', 10.00, 8.00],
            ['Huarina', 15.00, 10.00],
            ['Achacachi', 20.00, 15.00]
        ];

        // Añadir paradas específicas según el destino final
        // Esto es solo para simulación realista
        $paradasExtras = [];
        if (stripos($r->destino, 'COPACABANA') !== false) {
            $paradasExtras = [['Tiquina', 25.00, 20.00], ['Copacabana (Centro)', 35.00, 25.00]];
        } elseif (stripos($r->destino, 'ESCOMA') !== false || stripos($r->destino, 'CHAGUAYA') !== false || stripos($r->destino, 'CARABUCO') !== false) {
            $paradasExtras = [['Ancoraimes', 25.00, 20.00], ['Carabuco', 30.00, 25.00]];
            if (stripos($r->destino, 'CHAGUAYA') !== false) {
                $paradasExtras[] = ['Chaguaya', 40.00, 30.00];
            }
        }

        $todas = array_merge($paradasComunes, $paradasExtras);

        $orden = 1;
        foreach ($todas as $p) {
            $sql = "INSERT INTO rutas_paradas (ruta_id, nombre_parada, orden_index, precio_pasaje, precio_base_encomienda) VALUES (:rid, :nombre, :orden, :pasaje, :enc)";
            $db->query($sql);
            $db->bind(':rid', $r->id);
            $db->bind(':nombre', $p[0]);
            $db->bind(':orden', $orden++);
            $db->bind(':pasaje', $p[1]);
            $db->bind(':enc', $p[2]);
            $db->execute();
        }
    }
}

echo "✅ Proceso completado.";
