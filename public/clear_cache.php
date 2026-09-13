<?php

/**
 * Utilidad para limpiar el OPcache de PHP
 * Acceder a: http://localhost/venta-pasajes/clear_cache.php
 */

// Configuración de seguridad básica
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!in_array($client_ip, $allowed_ips)) {
    die('❌ Acceso denegado. Solo permitido desde localhost.');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpiar Caché - Sistema Venta Pasajes</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .subtitle {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        .result {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #e7f3ff;
            color: #004085;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.85rem;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🧹 Limpieza de Caché</h1>
        <p class="subtitle">Sistema de Venta de Pasajes</p>

        <?php
        $results = [];

        // 1. Limpiar OPcache
        if (function_exists('opcache_reset')) {
            if (opcache_reset()) {
                $results[] = [
                    'type' => 'success',
                    'message' => '✅ OPcache limpiado exitosamente'
                ];

                // Obtener estadísticas de OPcache
                $status = opcache_get_status();
                $config = opcache_get_configuration();
            } else {
                $results[] = [
                    'type' => 'error',
                    'message' => '❌ Error al limpiar OPcache'
                ];
            }
        } else {
            $results[] = [
                'type' => 'warning',
                'message' => '⚠️ OPcache no está habilitado en este servidor'
            ];
        }

        // 2. Limpiar caché de sesión (opcional)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // No destruir la sesión del usuario, solo limpiar variables de caché
        if (isset($_SESSION['cache'])) {
            unset($_SESSION['cache']);
            $results[] = [
                'type' => 'success',
                'message' => '✅ Caché de sesión limpiado'
            ];
        }

        // Mostrar resultados
        foreach ($results as $result) {
            echo '<div class="result ' . $result['type'] . '">' . $result['message'] . '</div>';
        }

        // Mostrar estadísticas de OPcache si está disponible
        if (isset($status) && $status !== false) {
            echo '<div class="stats">';
            echo '<div class="stat-card">';
            echo '<div class="stat-value">' . round($status['opcache_statistics']['opcache_hit_rate'], 2) . '%</div>';
            echo '<div class="stat-label">Hit Rate</div>';
            echo '</div>';

            echo '<div class="stat-card">';
            echo '<div class="stat-value">' . $status['opcache_statistics']['num_cached_scripts'] . '</div>';
            echo '<div class="stat-label">Scripts en Caché</div>';
            echo '</div>';

            echo '<div class="stat-card">';
            echo '<div class="stat-value">' . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . ' MB</div>';
            echo '<div class="stat-label">Memoria Usada</div>';
            echo '</div>';

            echo '<div class="stat-card">';
            echo '<div class="stat-value">' . round($status['memory_usage']['free_memory'] / 1024 / 1024, 2) . ' MB</div>';
            echo '<div class="stat-label">Memoria Libre</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>

        <div class="info">
            <strong>📝 Instrucciones adicionales:</strong><br>
            1. Después de limpiar el caché del servidor, limpia también el caché de tu navegador<br>
            2. En Chrome: Ctrl + Shift + Delete → Borrar "Imágenes y archivos en caché"<br>
            3. O usa: Ctrl + F5 para recargar forzadamente la página<br>
            4. Si los cambios aún no se reflejan, verifica que el archivo .htaccess tenga las directivas de caché
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="<?php echo $_SERVER['HTTP_REFERER'] ?? '/venta-pasajes'; ?>" class="btn">← Volver al Sistema</a>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn" style="background: #28a745; margin-left: 10px;">🔄 Limpiar de Nuevo</a>
        </div>
    </div>
</body>

</html>