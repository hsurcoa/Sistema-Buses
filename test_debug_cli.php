<?php
// test_debug_cli.php - Script de diagnóstico para ejecutar en terminal

echo "=== INICIO DIAGNOSTICO ===\n";

// 1. Definir rutas
$projectDir = __DIR__; // c:\xampp\htdocs\venta-pasajes
$backupDir = $projectDir . DIRECTORY_SEPARATOR . 'backups';

echo "Project Dir: $projectDir\n";
echo "Backup Dir:  $backupDir\n";

// 2. Verificar Directorio
if (!is_dir($backupDir)) {
    echo "ERROR: El directorio de backups NO existe.\n";
    exit(1);
} else {
    echo "OK: El directorio existe.\n";
}

// 3. Verificar Permisos del Directorio
if (is_writable($backupDir)) {
    echo "OK: El directorio es escribible.\n";
} else {
    echo "ERROR: El directorio NO es escribible.\n";
}

// 4. Listar Archivos
echo "\n--- Listado de Archivos ---\n";
$files = scandir($backupDir);
$foundZip = null;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    $fullPath = $backupDir . DIRECTORY_SEPARATOR . $file;
    $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
    $writable = is_writable($fullPath) ? 'SI' : 'NO';

    echo "Archivo: $file\n";
    echo "  - Ruta: $fullPath\n";
    echo "  - Permisos: $perms\n";
    echo "  - Escribible: $writable\n";

    // Guardar el primer zip que encontremos para probar
    if (pathinfo($file, PATHINFO_EXTENSION) === 'zip' && !$foundZip) {
        $foundZip = $file;
    }
}

// 5. Prueba de Creación y Eliminación (Dummy)
echo "\n--- Prueba de Escritura/Eliminación ---\n";
$dummyFile = $backupDir . DIRECTORY_SEPARATOR . 'test_delete_me.txt';

if (file_put_contents($dummyFile, "test content")) {
    echo "OK: Archivo de prueba creado exitosamente ($dummyFile).\n";

    if (unlink($dummyFile)) {
        echo "OK: Archivo de prueba eliminado exitosamente.\n";
    } else {
        echo "ERROR: No se pudo eliminar el archivo de prueba.\n";
        print_r(error_get_last());
    }
} else {
    echo "ERROR: No se pudo crear el archivo de prueba.\n";
    print_r(error_get_last());
}

echo "\n=== FIN DIAGNOSTICO ===\n";
