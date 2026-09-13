<?php

/**
 * DIAGNÓSTICO COMPLETO: Problema de Placa y Chofer no se muestran
 * Análisis exhaustivo de la base de datos y flujo de datos
 */

require_once 'conexion.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Diagnóstico: Placa y Chofer</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            margin-top: 0;
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
            font-weight: 600;
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

        .warning {
            background: #ffc;
            border-left: 4px solid #fa0;
            padding: 10px;
            margin: 10px 0;
        }

        .success {
            background: #efe;
            border-left: 4px solid #4a4;
            padding: 10px;
            margin: 10px 0;
        }

        .info {
            background: #def;
            border-left: 4px solid #49f;
            padding: 10px;
            margin: 10px 0;
        }

        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <h1>🔍 DIAGNÓSTICO COMPLETO: Problema Placa y Chofer No Se Muestran</h1>

    <?php
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=sistema_transportes;charset=utf8mb4", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo '<div class="success">✅ Conexión a base de datos exitosa</div>';

        // ==========================================
        // 1. ANÁLISIS DE ESTRUCTURA DE TABLAS
        // ==========================================
        echo '<div class="section">';
        echo '<h2>📋 1. Estructura de Tablas Relevantes</h2>';

        $tablas = ['buses', 'asignaciones_buses', 'tipos_buses', 'usuarios'];
        foreach ($tablas as $tabla) {
            echo "<h3>Tabla: <code>$tabla</code></h3>";
            $stmt = $pdo->query("DESCRIBE $tabla");
            $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '<table>';
            echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            foreach ($columnas as $col) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "<td>{$col['Extra']}</td>";
                echo "</tr>";
            }
            echo '</table>';
        }
        echo '</div>';

        // ==========================================
        // 2. ANÁLISIS DE DATOS: BUSES
        // ==========================================
        echo '<div class="section">';
        echo '<h2>🚌 2. Análisis de Buses Registrados</h2>';

        $stmt = $pdo->query("
            SELECT 
                b.id,
                b.placa,
                b.numero_interno,
                b.marca,
                b.modelo,
                b.tipo_bus_id,
                tb.nombre as tipo_bus,
                tb.pisos,
                b.estado
            FROM buses b
            LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
            ORDER BY b.id DESC
            LIMIT 20
        ");
        $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<p><strong>Total de buses en el sistema:</strong> ' . count($buses) . '</p>';

        if (count($buses) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Placa</th><th>Nº Interno</th><th>Marca</th><th>Modelo</th><th>Tipo Bus</th><th>Pisos</th><th>Estado</th></tr>';
            foreach ($buses as $bus) {
                $estadoClass = $bus['estado'] == 1 ? 'success' : 'error';
                $estadoTexto = $bus['estado'] == 1 ? '✅ Activo' : '❌ Inactivo';
                echo "<tr>";
                echo "<td>{$bus['id']}</td>";
                echo "<td><strong>{$bus['placa']}</strong></td>";
                echo "<td>{$bus['numero_interno']}</td>";
                echo "<td>{$bus['marca']}</td>";
                echo "<td>{$bus['modelo']}</td>";
                echo "<td>{$bus['tipo_bus']}</td>";
                echo "<td>{$bus['pisos']}</td>";
                echo "<td><span class='$estadoClass'>$estadoTexto</span></td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<div class="error">❌ NO HAY BUSES REGISTRADOS EN EL SISTEMA</div>';
        }
        echo '</div>';

        // ==========================================
        // 3. ANÁLISIS DE ASIGNACIONES
        // ==========================================
        echo '<div class="section">';
        echo '<h2>👥 3. Análisis de Asignaciones de Tripulación</h2>';

        $stmt = $pdo->query("
            SELECT 
                ab.id,
                ab.bus_id,
                b.placa,
                ab.chofer_id,
                CONCAT(u1.nombres, ' ', u1.apellidos) as nombre_chofer,
                ab.copiloto_id,
                CONCAT(u2.nombres, ' ', u2.apellidos) as nombre_copiloto,
                ab.estado,
                ab.fecha_asignacion
            FROM asignaciones_buses ab
            LEFT JOIN buses b ON ab.bus_id = b.id
            LEFT JOIN usuarios u1 ON ab.chofer_id = u1.id
            LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
            ORDER BY ab.id DESC
            LIMIT 20
        ");
        $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<p><strong>Total de asignaciones:</strong> ' . count($asignaciones) . '</p>';

        if (count($asignaciones) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Bus ID</th><th>Placa</th><th>Chofer ID</th><th>Nombre Chofer</th><th>Copiloto ID</th><th>Nombre Copiloto</th><th>Estado</th></tr>';
            foreach ($asignaciones as $asig) {
                $estadoClass = $asig['estado'] == 1 ? 'success' : 'error';
                $estadoTexto = $asig['estado'] == 1 ? '✅ Activa' : '❌ Inactiva';
                echo "<tr>";
                echo "<td>{$asig['id']}</td>";
                echo "<td>{$asig['bus_id']}</td>";
                echo "<td><strong>{$asig['placa']}</strong></td>";
                echo "<td>" . ($asig['chofer_id'] ?? '<em>NULL</em>') . "</td>";
                echo "<td>" . ($asig['nombre_chofer'] ?? '<em>Sin asignar</em>') . "</td>";
                echo "<td>" . ($asig['copiloto_id'] ?? '<em>NULL</em>') . "</td>";
                echo "<td>" . ($asig['nombre_copiloto'] ?? '<em>Sin asignar</em>') . "</td>";
                echo "<td><span class='$estadoClass'>$estadoTexto</span></td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<div class="error">❌ NO HAY ASIGNACIONES DE TRIPULACIÓN EN EL SISTEMA</div>';
        }
        echo '</div>';

        // ==========================================
        // 4. BUSES SIN ASIGNACIÓN
        // ==========================================
        echo '<div class="section">';
        echo '<h2>⚠️ 4. Buses Sin Asignación de Tripulación</h2>';

        $stmt = $pdo->query("
            SELECT 
                b.id,
                b.placa,
                b.numero_interno,
                tb.nombre as tipo_bus,
                b.estado
            FROM buses b
            LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
            LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id
            WHERE ab.id IS NULL AND b.estado = 1
        ");
        $busesSinAsignacion = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($busesSinAsignacion) > 0) {
            echo '<div class="warning">⚠️ Se encontraron ' . count($busesSinAsignacion) . ' buses activos sin asignación de tripulación</div>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Placa</th><th>Nº Interno</th><th>Tipo Bus</th><th>Estado</th></tr>';
            foreach ($busesSinAsignacion as $bus) {
                echo "<tr>";
                echo "<td>{$bus['id']}</td>";
                echo "<td><strong>{$bus['placa']}</strong></td>";
                echo "<td>{$bus['numero_interno']}</td>";
                echo "<td>{$bus['tipo_bus']}</td>";
                echo "<td>✅ Activo</td>";
                echo "</tr>";
            }
            echo '</table>';
            echo '<div class="info">💡 <strong>Recomendación:</strong> Estos buses necesitan una asignación en la tabla <code>asignaciones_buses</code></div>';
        } else {
            echo '<div class="success">✅ Todos los buses activos tienen asignación de tripulación</div>';
        }
        echo '</div>';

        // ==========================================
        // 5. SIMULACIÓN DE CONSULTA DEL SISTEMA
        // ==========================================
        echo '<div class="section">';
        echo '<h2>🔬 5. Simulación de Consulta del Sistema (obtenerTripulacionBus)</h2>';

        // Obtener el primer bus activo
        $stmt = $pdo->query("SELECT id, placa FROM buses WHERE estado = 1 LIMIT 1");
        $busTest = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($busTest) {
            echo "<p>Probando con Bus ID: <strong>{$busTest['id']}</strong> - Placa: <strong>{$busTest['placa']}</strong></p>";

            // Ejecutar la misma consulta que hace el sistema
            $stmt = $pdo->prepare("
                SELECT 
                    -- Datos de la asignación
                    ab.bus_id,
                    ab.chofer_id,
                    CONCAT(u.nombres, ' ', u.apellidos) as nombre_chofer,
                    ab.copiloto_id,
                    CONCAT(u2.nombres, ' ', u2.apellidos) as nombre_copiloto,
                    
                    -- Información completa del bus
                    b.placa as bus_placa,
                    b.numero_interno as bus_numero,
                    b.marca as bus_marca,
                    b.modelo as bus_modelo,
                    
                    -- Información del tipo de bus
                    tb.nombre as tipo_bus,
                    tb.pisos as bus_pisos,
                    tb.capacidad as bus_capacidad
                    
                FROM asignaciones_buses ab
                INNER JOIN buses b ON ab.bus_id = b.id
                LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
                LEFT JOIN usuarios u ON ab.chofer_id = u.id
                LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
                WHERE ab.bus_id = :bus_id AND ab.estado = 1
                LIMIT 1
            ");
            $stmt->execute(['bus_id' => $busTest['id']]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                echo '<div class="success">✅ La consulta retornó datos correctamente</div>';
                echo '<h3>Datos retornados:</h3>';
                echo '<pre>' . print_r($resultado, true) . '</pre>';

                // Verificar campos críticos
                echo '<h3>Verificación de Campos Críticos:</h3>';
                echo '<ul>';
                echo '<li><strong>bus_placa:</strong> ' . ($resultado['bus_placa'] ? "✅ {$resultado['bus_placa']}" : '❌ NULL') . '</li>';
                echo '<li><strong>nombre_chofer:</strong> ' . ($resultado['nombre_chofer'] ? "✅ {$resultado['nombre_chofer']}" : '⚠️ NULL (Sin chofer asignado)') . '</li>';
                echo '<li><strong>nombre_copiloto:</strong> ' . ($resultado['nombre_copiloto'] ? "✅ {$resultado['nombre_copiloto']}" : '⚠️ NULL (Sin copiloto asignado)') . '</li>';
                echo '<li><strong>tipo_bus:</strong> ' . ($resultado['tipo_bus'] ? "✅ {$resultado['tipo_bus']}" : '❌ NULL') . '</li>';
                echo '</ul>';
            } else {
                echo '<div class="error">❌ La consulta NO retornó datos para este bus</div>';
                echo '<div class="warning">⚠️ <strong>Causa probable:</strong> El bus no tiene registro en la tabla <code>asignaciones_buses</code></div>';
            }
        } else {
            echo '<div class="error">❌ No hay buses activos para probar</div>';
        }
        echo '</div>';

        // ==========================================
        // 6. ANÁLISIS DE USUARIOS (CHOFERES)
        // ==========================================
        echo '<div class="section">';
        echo '<h2>👨‍✈️ 6. Análisis de Usuarios (Choferes y Copilotos)</h2>';

        $stmt = $pdo->query("
            SELECT 
                id,
                nombres,
                apellidos,
                CONCAT(nombres, ' ', apellidos) as nombre_completo,
                perfil,
                estado
            FROM usuarios
            WHERE perfil IN ('Chofer', 'Copiloto')
            ORDER BY perfil, nombres
        ");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo '<p><strong>Total de choferes/copilotos:</strong> ' . count($usuarios) . '</p>';

        if (count($usuarios) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Nombre Completo</th><th>Perfil</th><th>Estado</th></tr>';
            foreach ($usuarios as $user) {
                $estadoClass = $user['estado'] == 1 ? 'success' : 'error';
                $estadoTexto = $user['estado'] == 1 ? '✅ Activo' : '❌ Inactivo';
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td><strong>{$user['nombre_completo']}</strong></td>";
                echo "<td>{$user['perfil']}</td>";
                echo "<td><span class='$estadoClass'>$estadoTexto</span></td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<div class="error">❌ NO HAY CHOFERES O COPILOTOS REGISTRADOS</div>';
        }
        echo '</div>';

        // ==========================================
        // 7. DIAGNÓSTICO FINAL Y RECOMENDACIONES
        // ==========================================
        echo '<div class="section">';
        echo '<h2>📊 7. Diagnóstico Final y Recomendaciones</h2>';

        $problemas = [];
        $recomendaciones = [];

        // Verificar buses sin asignación
        if (count($busesSinAsignacion) > 0) {
            $problemas[] = "Hay " . count($busesSinAsignacion) . " buses activos sin asignación de tripulación";
            $recomendaciones[] = "Crear registros en la tabla <code>asignaciones_buses</code> para estos buses";
        }

        // Verificar si hay buses
        if (count($buses) == 0) {
            $problemas[] = "No hay buses registrados en el sistema";
            $recomendaciones[] = "Registrar al menos un bus en la tabla <code>buses</code>";
        }

        // Verificar si hay choferes
        if (count($usuarios) == 0) {
            $problemas[] = "No hay choferes o copilotos registrados";
            $recomendaciones[] = "Registrar usuarios con perfil 'Chofer' o 'Copiloto'";
        }

        if (count($problemas) > 0) {
            echo '<h3>⚠️ Problemas Detectados:</h3>';
            echo '<ul>';
            foreach ($problemas as $problema) {
                echo "<li class='warning'>$problema</li>";
            }
            echo '</ul>';

            echo '<h3>💡 Recomendaciones:</h3>';
            echo '<ul>';
            foreach ($recomendaciones as $rec) {
                echo "<li class='info'>$rec</li>";
            }
            echo '</ul>';
        } else {
            echo '<div class="success">✅ No se detectaron problemas estructurales en la base de datos</div>';
        }

        echo '</div>';
    } catch (PDOException $e) {
        echo '<div class="error">❌ Error de base de datos: ' . $e->getMessage() . '</div>';
    }
    ?>

    <div class="section">
        <h2>📝 Conclusión</h2>
        <p>Este diagnóstico ha analizado:</p>
        <ul>
            <li>✅ Estructura de tablas relevantes</li>
            <li>✅ Datos de buses registrados</li>
            <li>✅ Asignaciones de tripulación</li>
            <li>✅ Buses sin asignación</li>
            <li>✅ Simulación de consulta del sistema</li>
            <li>✅ Usuarios (choferes y copilotos)</li>
        </ul>
        <p><strong>Revisa los resultados anteriores para identificar la causa raíz del problema.</strong></p>
    </div>
</body>

</html>