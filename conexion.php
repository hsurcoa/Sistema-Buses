<?php
// FILE: conexion.php
// Updated to use the central configuration

// Require config if not already loaded (check for constant)
if (!defined('DB_HOST')) {
    $configPath = __DIR__ . '/app/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        // Fallback or Error
        die("Error: No se encuentra app/config/config.php");
    }
}

try {
    // Use constants from config.php
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error crítico de conexión (conexion.php): " . $e->getMessage());
}
