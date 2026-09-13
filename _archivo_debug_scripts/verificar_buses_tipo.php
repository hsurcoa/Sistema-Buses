<?php

/**
 * Script de verificación: Buses por tipo
 */

require_once 'conexion.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Verificación de Buses por Tipo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }

        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }

        h2 {
            color: #667eea;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background: #667eea;
            color: white;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .error {
            background: #fee;
            border-left: 4px solid #f44;
            padding: 10px;
            margin: 10px 0;
        }

        .success {
            background: #efe;
            border-left: 4px solid #4a4;
            padding: 10px;
            margin: 10px 0;
        }

        .warning {
            background: #ffc;
            border-left: 4px solid #fa0;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1>🔍 Verificación de Buses por Tipo</h1>

    <?php
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=sistema_transportes;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo '<div class="success">✅ Conexión exitosa</div>';

        // 1. Listar todos los tipos de buses
        echo '<div class="section">';
        echo '<h2>📋 Tipos de Buses Registrados</h2>';
        $stmt = $pdo->query("SELECT id, nombre, capacidad, pisos, estado FROM tipos_buses ORDER BY id");
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($tipos) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Nombre</th><th>Capacidad</th><th>Pisos</th><th>Estado</th></tr>';
            foreach ($tipos as $tipo) {
                $estadoTexto = $tipo['estado'] == 1 ? '✅ Activo' : '❌ Inactivo';
                echo "<tr>";
                echo "<td><strong>{$tipo['id']}</strong></td>";
                echo "<td>{$tipo['nombre']}</td>";
                echo "<td>{$tipo['capacidad']} asientos</td>";
                echo "<td>{$tipo['pisos']} piso(s)</td>";
                echo "<td>$estadoTexto</td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<div class="error">❌ No hay tipos de buses registrados</div>';
        }
        echo '</div>';

        // 2. Listar buses y su tipo asignado
        echo '<div class="section">';
        echo '<h2>🚌 Buses Registrados y su Tipo</h2>';
        $stmt = $pdo->query("
            SELECT 
                b.id,
                b.placa,
                b.marca,
                b.numero_interno,
                b.tipo_bus_id,
                tb.nombre as tipo_bus_nombre,
                b.estado
            FROM buses b
            LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
            ORDER BY b.id
        ");
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($buses) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Placa</th><th>Marca</th><th>Nº Interno</th><th>Tipo Bus ID</th><th>Tipo Bus Nombre</th><th>Estado</th></tr>';
            foreach ($buses as $bus) {
                $estadoTexto = $bus['estado'] == 1 ? '✅ Activo' : '❌ Inactivo';
                $tipoNombre = $bus['tipo_bus_nombre'] ?? '<span style="color:red">❌ SIN TIPO</span>';
                echo "<tr>";
                echo "<td>{$bus['id']}</td>";
                echo "<td><strong>{$bus['placa']}</strong></td>";
                echo "<td>{$bus['marca']}</td>";
                echo "<td>{$bus['numero_interno']}</td>";
                echo "<td>{$bus['tipo_bus_id']}</td>";
                echo "<td>$tipoNombre</td>";
                echo "<td>$estadoTexto</td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<div class="error">❌ No hay buses registrados</div>';
        }
        echo '</div>';

        // 3. Verificar buses por cada tipo
        echo '<div class="section">';
        echo '<h2>🔬 Buses Disponibles por Tipo (Simulación de obtenerBusesPorTipo)</h2>';

        foreach ($tipos as $tipo) {
            echo "<h3>Tipo: {$tipo['nombre']} (ID: {$tipo['id']})</h3>";

            $stmt = $pdo->prepare("
                SELECT id, placa, marca, numero_interno 
                FROM buses 
                WHERE tipo_bus_id = :tipo_id AND estado = 1
            ");
            $stmt->execute(['tipo_id' => $tipo['id']]);
            $busesDelTipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($busesDelTipo) > 0) {
                echo '<div class="success">✅ Se encontraron ' . count($busesDelTipo) . ' buses activos de este tipo</div>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Placa</th><th>Marca</th><th>Nº Interno</th></tr>';
                foreach ($busesDelTipo as $bus) {
                    echo "<tr>";
                    echo "<td>{$bus['id']}</td>";
                    echo "<td><strong>{$bus['placa']}</strong></td>";
                    echo "<td>{$bus['marca']}</td>";
                    echo "<td>{$bus['numero_interno']}</td>";
                    echo "</tr>";
                }
                echo '</table>';
            } else {
                echo '<div class="warning">⚠️ No hay buses activos de este tipo</div>';

                // Verificar si hay buses inactivos
                $stmt = $pdo->prepare("
                    SELECT id, placa, estado 
                    FROM buses 
                    WHERE tipo_bus_id = :tipo_id
                ");
                $stmt->execute(['tipo_id' => $tipo['id']]);
                $busesInactivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($busesInactivos) > 0) {
                    echo '<div class="warning">⚠️ Hay ' . count($busesInactivos) . ' buses de este tipo pero están INACTIVOS</div>';
                } else {
                    echo '<div class="error">❌ No hay buses registrados con este tipo_bus_id</div>';
                }
            }
        }
        echo '</div>';
    } catch (PDOException $e) {
        echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    }
    ?>
</body>

</html>