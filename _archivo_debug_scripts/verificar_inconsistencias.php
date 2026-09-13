<?php

/**
 * Verificar inconsistencias entre tablas buses y vehiculos
 */

require_once 'conexion.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Verificación de Inconsistencias</title>
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

        .info {
            background: #def;
            border-left: 4px solid #49f;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1>🔍 Verificación de Inconsistencias entre Tablas</h1>

    <?php
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=sistema_transportes;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo '<div class="success">✅ Conexión exitosa</div>';

        // 1. Verificar si existe la tabla vehiculos
        echo '<div class="section">';
        echo '<h2>📋 1. Verificación de Tablas</h2>';

        $stmt = $pdo->query("SHOW TABLES LIKE 'vehiculos'");
        $existeVehiculos = $stmt->rowCount() > 0;

        $stmt = $pdo->query("SHOW TABLES LIKE 'buses'");
        $existeBuses = $stmt->rowCount() > 0;

        if ($existeVehiculos) {
            echo '<div class="success">✅ Tabla "vehiculos" existe</div>';
        } else {
            echo '<div class="error">❌ Tabla "vehiculos" NO existe</div>';
        }

        if ($existeBuses) {
            echo '<div class="success">✅ Tabla "buses" existe</div>';
        } else {
            echo '<div class="error">❌ Tabla "buses" NO existe</div>';
        }
        echo '</div>';

        // 2. Comparar contenido de ambas tablas
        if ($existeVehiculos && $existeBuses) {
            echo '<div class="section">';
            echo '<h2>🚌 2. Comparación de Contenido</h2>';

            // Contar registros en vehiculos
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM vehiculos WHERE estado = 1");
            $totalVehiculos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Contar registros en buses
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM buses WHERE estado = 1");
            $totalBuses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            echo "<p><strong>Vehículos activos en tabla 'vehiculos':</strong> $totalVehiculos</p>";
            echo "<p><strong>Buses activos en tabla 'buses':</strong> $totalBuses</p>";

            if ($totalVehiculos > $totalBuses) {
                echo '<div class="warning">⚠️ Hay más vehículos en la tabla "vehiculos" que en "buses"</div>';
                echo '<div class="info">💡 Esto sugiere que el sistema usa la tabla "vehiculos" como principal</div>';
            } elseif ($totalBuses > $totalVehiculos) {
                echo '<div class="warning">⚠️ Hay más buses en la tabla "buses" que en "vehiculos"</div>';
                echo '<div class="info">💡 Esto sugiere que el sistema usa la tabla "buses" como principal</div>';
            } else {
                echo '<div class="success">✅ Ambas tablas tienen la misma cantidad de registros</div>';
            }

            echo '</div>';

            // 3. Mostrar vehículos de la tabla vehiculos
            echo '<div class="section">';
            echo '<h2>🚗 3. Vehículos en tabla "vehiculos"</h2>';

            $stmt = $pdo->query("
                SELECT 
                    v.id,
                    v.placa,
                    v.marca,
                    v.modelo,
                    v.estado,
                    v.tipo_servicio,
                    v.asientos
                FROM vehiculos v
                WHERE v.estado = 1
                ORDER BY v.id
            ");
            $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($vehiculos) > 0) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Tipo Servicio</th><th>Asientos</th><th>Estado</th></tr>';
                foreach ($vehiculos as $v) {
                    echo "<tr>";
                    echo "<td>{$v['id']}</td>";
                    echo "<td><strong>{$v['placa']}</strong></td>";
                    echo "<td>{$v['marca']}</td>";
                    echo "<td>{$v['modelo']}</td>";
                    echo "<td>{$v['tipo_servicio']}</td>";
                    echo "<td>{$v['asientos']}</td>";
                    echo "<td>✅ Activo</td>";
                    echo "</tr>";
                }
                echo '</table>';
            } else {
                echo '<div class="error">❌ No hay vehículos activos en la tabla "vehiculos"</div>';
            }
            echo '</div>';
        }

        // 4. Recomendación final
        echo '<div class="section">';
        echo '<h2>💡 4. Recomendación</h2>';
        echo '<div class="info">';
        echo '<p><strong>PROBLEMA IDENTIFICADO:</strong></p>';
        echo '<ul>';
        echo '<li>El sistema tiene DOS tablas para almacenar buses: <code>buses</code> y <code>vehiculos</code></li>';
        echo '<li>El método <code>obtenerBusesPorTipo()</code> consulta la tabla <code>buses</code></li>';
        echo '<li>La página de registro de buses guarda en la tabla <code>vehiculos</code></li>';
        echo '<li>Esto causa una <strong>INCONSISTENCIA</strong> en el sistema</li>';
        echo '</ul>';
        echo '<p><strong>SOLUCIÓN:</strong></p>';
        echo '<ol>';
        echo '<li>Modificar <code>RutaModel.php</code> para que consulte la tabla <code>vehiculos</code> en lugar de <code>buses</code></li>';
        echo '<li>O bien, sincronizar los datos entre ambas tablas</li>';
        echo '</ol>';
        echo '</div>';
        echo '</div>';
    } catch (PDOException $e) {
        echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    }
    ?>
</body>

</html>