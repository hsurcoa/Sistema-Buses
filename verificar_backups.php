<?php
// Script de verificación de backups
// Guarda esto como: c:\xampp\htdocs\venta-pasajes\verificar_backups.php

echo "<h2>Verificación de Backups</h2>";

$projectDir = dirname(__FILE__);
$backupDir = $projectDir . DIRECTORY_SEPARATOR . 'backups';

echo "<p><strong>Directorio del proyecto:</strong> $projectDir</p>";
echo "<p><strong>Directorio de backups:</strong> $backupDir</p>";
echo "<p><strong>¿Existe el directorio?</strong> " . (is_dir($backupDir) ? 'SÍ' : 'NO') . "</p>";

if (is_dir($backupDir)) {
    echo "<h3>Archivos en el directorio:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Nombre</th><th>Ruta Completa</th><th>Tamaño</th><th>Permisos</th><th>¿Escribible?</th></tr>";

    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $backupDir . DIRECTORY_SEPARATOR . $file;
            $size = filesize($filePath);
            $perms = substr(sprintf('%o', fileperms($filePath)), -4);
            $writable = is_writable($filePath) ? 'SÍ' : 'NO';

            echo "<tr>";
            echo "<td>$file</td>";
            echo "<td>$filePath</td>";
            echo "<td>" . number_format($size) . " bytes</td>";
            echo "<td>$perms</td>";
            echo "<td>$writable</td>";
            echo "</tr>";
        }
    }
    echo "</table>";

    echo "<h3>Prueba de Eliminación</h3>";
    echo "<p>Selecciona un archivo para probar la eliminación:</p>";
    echo "<form method='POST'>";
    echo "<select name='test_file'>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<option value='$file'>$file</option>";
        }
    }
    echo "</select>";
    echo "<button type='submit' name='test_delete'>Probar Eliminación</button>";
    echo "</form>";

    if (isset($_POST['test_delete']) && !empty($_POST['test_file'])) {
        $testFile = $_POST['test_file'];
        $testPath = $backupDir . DIRECTORY_SEPARATOR . $testFile;

        echo "<div style='background: #ffffcc; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Resultado de la Prueba:</h4>";
        echo "<p><strong>Archivo:</strong> $testFile</p>";
        echo "<p><strong>Ruta:</strong> $testPath</p>";
        echo "<p><strong>¿Existe?</strong> " . (file_exists($testPath) ? 'SÍ' : 'NO') . "</p>";
        echo "<p><strong>¿Escribible?</strong> " . (is_writable($testPath) ? 'SÍ' : 'NO') . "</p>";

        if (file_exists($testPath) && is_writable($testPath)) {
            if (unlink($testPath)) {
                echo "<p style='color: green;'><strong>✓ ÉXITO:</strong> El archivo se eliminó correctamente</p>";
            } else {
                echo "<p style='color: red;'><strong>✗ ERROR:</strong> unlink() falló</p>";
                $error = error_get_last();
                echo "<pre>" . print_r($error, true) . "</pre>";
            }
        } else {
            echo "<p style='color: red;'><strong>✗ ERROR:</strong> El archivo no existe o no es escribible</p>";
        }
        echo "</div>";
    }
}
