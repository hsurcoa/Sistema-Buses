<?php
// Script de diagnóstico del módulo de Caja
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico del Módulo de Caja</h1>";

// 1. Verificar conexión a BD
require_once '../app/config/config.php';
require_once '../app/core/Database.php';

$db = new Database();

echo "<h2>1. Verificando tablas...</h2>";

// Verificar tabla cajas_sesiones
try {
    $db->query("SHOW TABLES LIKE 'cajas_sesiones'");
    $result = $db->single();
    if ($result) {
        echo "✅ Tabla 'cajas_sesiones' existe<br>";

        // Mostrar estructura
        $db->query("DESCRIBE cajas_sesiones");
        $columns = $db->resultSet();
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "❌ Tabla 'cajas_sesiones' NO existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Verificar tabla movimientos_caja
try {
    $db->query("SHOW TABLES LIKE 'movimientos_caja'");
    $result = $db->single();
    if ($result) {
        echo "✅ Tabla 'movimientos_caja' existe<br>";

        // Mostrar estructura
        $db->query("DESCRIBE movimientos_caja");
        $columns = $db->resultSet();
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "❌ Tabla 'movimientos_caja' NO existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>2. Verificando archivos del módulo...</h2>";

$files = [
    '../app/controllers/Caja.php',
    '../app/models/CajaModel.php',
    '../app/views/caja/apertura.php',
    '../app/views/caja/index.php',
    '../app/views/caja/reporte.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file NO existe<br>";
    }
}

echo "<h2>3. Probando CajaModel...</h2>";

try {
    require_once '../app/models/CajaModel.php';
    $cajaModel = new CajaModel();
    echo "✅ CajaModel se instanció correctamente<br>";

    // Probar verificarCajaAbierta con usuario ID 1
    $caja = $cajaModel->verificarCajaAbierta(1);
    if ($caja) {
        echo "✅ Usuario 1 tiene caja abierta: ID " . $caja->id . "<br>";
    } else {
        echo "ℹ️ Usuario 1 NO tiene caja abierta (normal si es primera vez)<br>";
    }
} catch (Exception $e) {
    echo "❌ Error al instanciar CajaModel: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Verificando sesión de usuario...</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ Usuario logueado: ID " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ No hay usuario logueado en la sesión<br>";
}

echo "<hr>";
echo "<p><strong>Diagnóstico completado.</strong></p>";
echo "<p><a href='" . URLROOT . "/caja'>Ir al módulo de Caja</a></p>";
