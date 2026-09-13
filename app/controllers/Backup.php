<?php
class Backup extends Controller
{
    public function __construct() {}

    public function index()
    {
        // Get list of existing backups
        $projectDir = dirname(dirname(dirname(__FILE__)));
        $backupDir = $projectDir . DIRECTORY_SEPARATOR . 'backups';

        $backups = [];

        if (file_exists($backupDir)) {
            $files = scandir($backupDir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $filePath = $backupDir . DIRECTORY_SEPARATOR . $file;
                    $fileSize = filesize($filePath);
                    $fileDate = date('d/m/Y H:i:s', filemtime($filePath));

                    // Determine type
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    $type = ($extension == 'sql') ? 'sql' : 'zip';

                    // Format size
                    $size = $this->formatBytes($fileSize);

                    $backups[] = [
                        'name' => $file,
                        'type' => $type,
                        'size' => $size,
                        'date' => $fileDate,
                        'path' => $filePath
                    ];
                }
            }

            // Sort by date (newest first)
            usort($backups, function ($a, $b) {
                return filemtime($b['path']) - filemtime($a['path']);
            });
        }

        $data = [
            'title' => 'Copias de Seguridad',
            'backups' => $backups
        ];

        $this->view('backup/index', $data);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function download($filename = '')
    {
        if (empty($filename)) {
            die('Archivo no especificado');
        }

        $projectDir = dirname(dirname(dirname(__FILE__)));
        $backupDir = $projectDir . DIRECTORY_SEPARATOR . 'backups';
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($filePath)) {
            // Limpiar cualquier output previo
            if (ob_get_level()) ob_end_clean();

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            // Headers de seguridad adicionales
            header('X-Content-Type-Options: nosniff');

            readfile($filePath);
            exit;
        }
    }

    public function delete($filename = '')
    {
        // Limpiar cualquier salida previa
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        // Log para debugging
        error_log("=== DELETE BACKUP ===");
        error_log("Filename recibido: " . $filename);

        if (empty($filename)) {
            error_log("ERROR: Filename vacío");
            echo json_encode([
                'status' => 'error',
                'message' => 'Archivo no especificado'
            ]);
            exit;
        }

        // Decodificar el nombre del archivo - COMENTADO por posible doble decodificación
        // $filename = urldecode($filename);
        error_log("Filename recibido (sin urldecode): " . $filename);

        $projectDir = dirname(dirname(dirname(__FILE__)));
        $backupDir = $projectDir . DIRECTORY_SEPARATOR . 'backups';

        // Usar basename por seguridad y limpieza
        $cleanFilename = basename($filename);
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $cleanFilename;

        error_log("Backup Dir: " . $backupDir);
        error_log("File Path: " . $filePath);
        error_log("File exists: " . (file_exists($filePath) ? 'SI' : 'NO'));

        // Verificar directorio
        if (!is_dir($backupDir)) {
            error_log("ERROR: Directorio no existe");
            echo json_encode([
                'status' => 'error',
                'message' => 'El directorio de backups no existe'
            ]);
            exit;
        }

        if (!file_exists($filePath)) {
            // FIX: Idempotencia. Si el archivo no existe, retornamos éxito para que la UI se actualice
            // y elimine la entrada fantasma de la lista.

            $debugLogPath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'debug_errors.log';
            error_log("BACKUP WARNING: Cleaning up ghost entry: " . $filePath, 3, $debugLogPath);

            echo json_encode([
                'status' => 'success',
                'message' => 'Entrada limpiada (el archivo ya no existía en disco)',
            ]);
            exit;
        }

        // Verificar permisos
        if (!is_writable($filePath)) {
            error_log("ERROR: Sin permisos");
            echo json_encode([
                'status' => 'error',
                'message' => 'Sin permisos para eliminar'
            ]);
            exit;
        }

        try {
            if (unlink($filePath)) {
                error_log("SUCCESS: Archivo eliminado");
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Backup eliminado exitosamente'
                ]);
            } else {
                error_log("ERROR: unlink falló");
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo eliminar (unlink falló)'
                ]);
            }
        } catch (Exception $e) {
            error_log("EXCEPTION: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    public function generate()
    {
        // 1. Configuración de Rutas
        // Root del proyecto (c:/xampp/htdocs/venta-pasajes)
        $projectDir = dirname(dirname(dirname(__FILE__)));
        $backupDir = $projectDir . DIRECTORY_SEPARATOR . 'backups';

        // Crear directorio si no existe
        if (!file_exists($backupDir)) {
            if (!mkdir($backupDir, 0755, true)) {
                die('Error Crítico: No se pudo crear el directorio de backups en ' . $backupDir);
            }
        }

        // Timestamp para nombres
        $timestamp = date('Y-m-d_H-i');

        // ---------------------------------------------------------
        // 2. BACKUP DE BASE DE DATOS
        // ---------------------------------------------------------
        $dbHost = DB_HOST;
        $dbUser = DB_USER;
        $dbPass = DB_PASS; // Puede estar vacía
        $dbName = 'sistema_transportes'; // Requerimiento específico del usuario

        $sqlFileName = "backup_db_{$timestamp}.sql";
        $sqlFilePath = $backupDir . DIRECTORY_SEPARATOR . $sqlFileName;

        // Construcción del comando mysqldump
        // Nota: En Windows con XAMPP, a veces mysqldump no está en el PATH global.
        // Intentaremos primero el comando global, y si falla, rutas comunes de XAMPP.

        $cmd = "mysqldump --host={$dbHost} --user={$dbUser}";
        if (!empty($dbPass)) {
            $cmd .= " --password={$dbPass}";
        }
        $cmd .= " {$dbName} > \"{$sqlFilePath}\"";

        $output = [];
        $returnVar = 0;

        // Ejecutar comando
        exec($cmd . ' 2>&1', $output, $returnVar);

        // Si falla (código distinto de 0), intentar con ruta absoluta de XAMPP
        if ($returnVar !== 0) {
            // Rutas posibles de mysqldump en XAMPP
            $mysqldumpPath = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
            if (file_exists($mysqldumpPath)) {
                $cmd = "\"{$mysqldumpPath}\" --host={$dbHost} --user={$dbUser}";
                if (!empty($dbPass)) {
                    $cmd .= " --password={$dbPass}";
                }
                $cmd .= " {$dbName} > \"{$sqlFilePath}\"";
                exec($cmd . ' 2>&1', $output, $returnVar);
            }
        }

        if ($returnVar !== 0) {
            $errorMsg = implode("\n", $output);
            die("Error al realizar backup de la base de datos '{$dbName}'.<br>Verifique que la BD existe y las credenciales son correctas.<br>Detalle: {$errorMsg}");
        }

        // ---------------------------------------------------------
        // 3. BACKUP DE ARCHIVOS (ZIP)
        // ---------------------------------------------------------
        $zipFileName = "backup_files_{$timestamp}.zip";
        $zipFilePath = $backupDir . DIRECTORY_SEPARATOR . $zipFileName;

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

            // Iterador recursivo para leer todos los archivos
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($projectDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                // Ignorar directorios (solo agregamos archivos, la estructura se mantiene)
                if ($file->isDir()) {
                    continue;
                }

                $filePath = $file->getRealPath();

                // Calcular ruta relativa para el ZIP
                // Ejemplo: c:/xampp/htdocs/venta-pasajes/index.php -> index.php
                $relativePath = substr($filePath, strlen($projectDir) + 1);

                // --- EXCLUSIONES ---

                // 1. Excluir carpeta backups (CRÍTICO para evitar recursión infinita)
                if (strpos($filePath, $backupDir) === 0) {
                    continue;
                }

                // 2. Excluir archivos .git (opcional pero recomendado)
                if (strpos($relativePath, '.git') === 0) {
                    continue;
                }

                // Agregar archivo al ZIP
                $zip->addFile($filePath, $relativePath);
            }

            $zip->close();
        } else {
            die("Error al crear el archivo ZIP.");
        }

        // ---------------------------------------------------------
        // 4. RETORNO A LA VISTA / ALERTA
        // ---------------------------------------------------------
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => '¡Copia de Seguridad Exitosa!',
            'details' => [
                'database' => $sqlFileName,
                'files' => $zipFileName,
                'location' => '/venta-pasajes/backups/'
            ]
        ]);
        exit;
    }
}
