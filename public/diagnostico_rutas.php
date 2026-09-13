<?php

/**
 * Script de Diagnóstico - Verificar Rutas en Base de Datos
 * Este script verifica el estado de las rutas programadas en la base de datos
 */

// Incluir configuración
require_once '../app/config/config.php';

// Crear conexión PDO
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Diagnóstico de Rutas Programadas</h1>";
    echo "<hr>";

    // 1. Contar total de viajes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM viajes");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Total de Viajes en BD: " . $total['total'] . "</h2>";

    // 2. Listar todos los viajes
    $sql = "SELECT 
            v.id,
            v.fecha_salida,
            v.hora_salida,
            v.estado,
            r.origen,
            r.destino,
            tb.nombre as tipo_bus
        FROM viajes v
        INNER JOIN rutas r ON v.ruta_id = r.id
        LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
        ORDER BY v.id DESC";

    $stmt = $pdo->query($sql);
    $viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Lista de Viajes:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #333; color: white;'>
            <th>ID</th>
            <th>Ruta</th>
            <th>Fecha Salida</th>
            <th>Hora Salida</th>
            <th>Tipo Bus</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>";

    foreach ($viajes as $viaje) {
        $estadoColor = $viaje['estado'] == 'activo' ? 'green' : 'orange';
        echo "<tr>";
        echo "<td><strong>{$viaje['id']}</strong></td>";
        echo "<td>{$viaje['origen']} → {$viaje['destino']}</td>";
        echo "<td>{$viaje['fecha_salida']}</td>";
        echo "<td>{$viaje['hora_salida']}</td>";
        echo "<td>{$viaje['tipo_bus']}</td>";
        echo "<td style='color: {$estadoColor}; font-weight: bold;'>{$viaje['estado']}</td>";
        echo "<td><a href='?eliminar={$viaje['id']}' onclick='return confirm(\"¿Eliminar viaje ID {$viaje['id']}?\")' style='color: red;'>Eliminar</a></td>";
        echo "</tr>";
    }
    echo "</table>";

    // 3. Verificar duplicados
    echo "<h2>Verificar Duplicados:</h2>";
    $sql = "SELECT 
            r.origen,
            r.destino,
            v.fecha_salida,
            v.hora_salida,
            COUNT(*) as cantidad
        FROM viajes v
        INNER JOIN rutas r ON v.ruta_id = r.id
        GROUP BY r.origen, r.destino, v.fecha_salida, v.hora_salida
        HAVING cantidad > 1";

    $stmt = $pdo->query($sql);
    $duplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($duplicados) > 0) {
        echo "<p style='color: red; font-weight: bold;'>⚠️ Se encontraron " . count($duplicados) . " grupos de viajes duplicados:</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f44; color: white;'>
                <th>Ruta</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Cantidad</th>
              </tr>";
        foreach ($duplicados as $dup) {
            echo "<tr>";
            echo "<td>{$dup['origen']} → {$dup['destino']}</td>";
            echo "<td>{$dup['fecha_salida']}</td>";
            echo "<td>{$dup['hora_salida']}</td>";
            echo "<td style='color: red; font-weight: bold;'>{$dup['cantidad']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ No se encontraron duplicados</p>";
    }

    // 4. Procesar eliminación si se solicitó
    if (isset($_GET['eliminar'])) {
        $idEliminar = (int)$_GET['eliminar'];

        // PASO 0: Eliminar encomiendas asociadas (FK constraint adicional detectado)
        try {
            $stmtEncomiendas = $pdo->prepare("DELETE FROM encomiendas WHERE viaje_id = :id");
            $stmtEncomiendas->bindParam(':id', $idEliminar, PDO::PARAM_INT);
            $stmtEncomiendas->execute();
        } catch (Exception $e) { /* Ignorar si no existe */
        }

        // PASO 1: Eliminar boletos asociados a este viaje
        try {
            $stmtBoletos = $pdo->prepare("DELETE FROM boletos WHERE viaje_id = :id");
            $stmtBoletos->bindParam(':id', $idEliminar, PDO::PARAM_INT);
            $stmtBoletos->execute();
        } catch (Exception $e) { /* Ignorar si no existe */
        }

        // PASO 2: Eliminar el viaje
        $stmt = $pdo->prepare("DELETE FROM viajes WHERE id = :id");
        $stmt->bindParam(':id', $idEliminar, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo "<script>alert('Viaje ID {$idEliminar} eliminado exitosamente (boletos y encomiendas limpiados)'); window.location.href='diagnostico_rutas.php';</script>";
        } else {
            echo "<p style='color: red;'>Error al eliminar viaje ID {$idEliminar}</p>";
        }
    }

    // 5. Procesar VACIADO COMPLETO
    if (isset($_GET['vaciar']) && $_GET['vaciar'] == 'todo') {
        try {
            // Desactivar checks de foreign keys temporalmente para limpieza masiva
            $pdo->query("SET FOREIGN_KEY_CHECKS = 0");

            // Vaciar tablas relacionadas
            $pdo->query("DELETE FROM encomiendas");
            $pdo->query("DELETE FROM boletos");
            $pdo->query("DELETE FROM viajes");

            // Resetear AUTO_INCREMENT
            try {
                $pdo->query("ALTER TABLE viajes AUTO_INCREMENT = 1");
            } catch (Exception $ev) {
            }

            // Reactivar checks
            $pdo->query("SET FOREIGN_KEY_CHECKS = 1");

            echo "<script>alert('¡Limpieza completa! Se eliminaron viajes, boletos y encomiendas.'); window.location.href='diagnostico_rutas.php';</script>";
        } catch (PDOException $e) {
            // Asegurarnos de reactivar checks en caso de error
            $pdo->query("SET FOREIGN_KEY_CHECKS = 1");
            echo "<p style='color: red;'>Error al vaciar las tablas: " . $e->getMessage() . "</p>";
        }
    }

    echo "<hr>";
    echo "<p class='actions-bar'>";
    echo "<a href='diagnostico_rutas.php' class='btn btn-refresh'>🔄 Recargar</a>";
    echo "<a href='" . URLROOT . "/ventas/crear_ruta' class='btn btn-back'>← Volver a Crear Rutas</a>";
    echo "<a href='?vaciar=todo' class='btn btn-danger' onclick='return confirm(\"¿ESTÁS SEGURO? Se borrarán TODOS los viajes programados, boletos y encomiendas. Esta acción no se puede deshacer.\")'>🗑️ BORRAR TODOS LOS VIAJES</a>";
    echo "</p>";
} catch (PDOException $e) {
    echo "<h1 style='color: red;'>Error de Base de Datos</h1>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba; color: #856404;'>";
    echo "<strong>Detalles del error:</strong> <br>" . $e->getMessage();
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        background: #f5f5f5;
    }

    table {
        background: white;
        margin: 20px 0;
    }

    h1,
    h2 {
        color: #333;
    }

    .actions-bar {
        display: flex;
        gap: 15px;
    }

    .btn {
        padding: 10px 20px;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        display: inline-block;
    }

    .btn-refresh {
        background: #007bff;
    }

    .btn-back {
        background: #28a745;
    }

    .btn-danger {
        background: #c82333;
    }
</style>