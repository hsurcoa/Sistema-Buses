<?php

namespace App\Http\Controllers;

/**
 * Copias de seguridad (BD + archivos). Puerto de
 * `legacy/app/controllers/Backup.php` (Fase 8, última del roadmap original
 * antes del cierre).
 *
 * Correcciones aplicadas (no solo transcripción):
 * - El legacy calculaba el directorio de proyecto con `dirname(dirname(dirname(__FILE__)))`
 *   desde `legacy/app/controllers/Backup.php`, lo que hoy resuelve a `legacy/`
 *   (un directorio que no existe: `legacy/backups`) en vez de la raíz del
 *   proyecto — efecto colateral de mover el controlador a `legacy/` en la
 *   Fase 1. El backup real ya existente vive en `<raiz>/backups/` (verificado
 *   en disco). Se usa `base_path('backups')` explícitamente.
 * - El ZIP zipeaba `legacy/` completo; ahora que el código vivo es la raíz
 *   del proyecto, se zipea `base_path()` excluyendo `backups/`, `.git/`,
 *   `vendor/`, `node_modules/` y `storage/framework|logs` (generados/
 *   reproducibles, no datos de usuario — incluirlos infla el ZIP sin aportar
 *   nada recuperable que `composer install`/`npm install` no reconstruya).
 * - `download()` no sanitizaba `$filename` con `basename()` (sí lo hacía
 *   `delete()`): un `../../` en la URL permitía leer cualquier archivo del
 *   servidor como descarga binaria (path traversal). Se corrigió aplicando
 *   `basename()` también ahí.
 * - Sin `requireAuth()` en el controlador legacy (hallazgo del spec de
 *   migración, sección 6); cerrado automáticamente por vivir esta ruta
 *   dentro del grupo `auth`+`active` de `routes/web.php`, igual que el
 *   resto de módulos migrados.
 */
class BackupController extends Controller
{
    /** Carpetas fuera de `backups/` que no aportan como backup (generadas/reproducibles). */
    private const EXCLUIR_PREFIJOS = ['backups', '.git', 'vendor', 'node_modules', 'storage/framework', 'storage/logs'];

    private function backupDir(): string
    {
        $dir = base_path('backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public function index()
    {
        $backupDir = $this->backupDir();
        $backups = [];

        foreach (scandir($backupDir) as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') {
                continue;
            }
            $filePath = $backupDir.DIRECTORY_SEPARATOR.$file;
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            $backups[] = [
                'name' => $file,
                'type' => $extension === 'sql' ? 'sql' : 'zip',
                'size' => $this->formatBytes(filesize($filePath)),
                'date' => date('d/m/Y H:i:s', filemtime($filePath)),
                'path' => $filePath,
            ];
        }

        usort($backups, fn ($a, $b) => filemtime($b['path']) - filemtime($a['path']));

        return view('backup.index', ['data' => [
            'title' => 'Copias de Seguridad',
            'backups' => $backups,
        ]]);
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        for (; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    public function download(string $filename = '')
    {
        if ($filename === '') {
            abort(400, 'Archivo no especificado');
        }

        $filePath = $this->backupDir().DIRECTORY_SEPARATOR.basename($filename);
        if (! file_exists($filePath)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->download($filePath, basename($filePath), [
            'Content-Description' => 'File Transfer',
            'Content-Transfer-Encoding' => 'binary',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(string $filename = '')
    {
        if ($filename === '') {
            return response()->json(['status' => 'error', 'message' => 'Archivo no especificado']);
        }

        $filePath = $this->backupDir().DIRECTORY_SEPARATOR.basename($filename);

        if (! file_exists($filePath)) {
            // Idempotente: si ya no existe en disco, se limpia la entrada fantasma de la lista.
            return response()->json(['status' => 'success', 'message' => 'Entrada limpiada (el archivo ya no existía en disco)']);
        }
        if (! is_writable($filePath)) {
            return response()->json(['status' => 'error', 'message' => 'Sin permisos para eliminar']);
        }

        return unlink($filePath)
            ? response()->json(['status' => 'success', 'message' => 'Backup eliminado exitosamente'])
            : response()->json(['status' => 'error', 'message' => 'No se pudo eliminar (unlink falló)']);
    }

    public function generate()
    {
        $projectDir = base_path();
        $backupDir = $this->backupDir();
        $timestamp = date('Y-m-d_H-i');

        // --- Backup de base de datos ---
        $dbHost = env('DB_HOST', 'localhost');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbName = env('DB_DATABASE', 'sistema_transportes');

        $sqlFileName = "backup_db_{$timestamp}.sql";
        $sqlFilePath = $backupDir.DIRECTORY_SEPARATOR.$sqlFileName;

        $cmd = "mysqldump --host={$dbHost} --user={$dbUser}";
        if ($dbPass !== '') {
            $cmd .= " --password={$dbPass}";
        }
        $cmd .= " {$dbName} > \"{$sqlFilePath}\"";

        $output = [];
        $returnVar = 0;
        exec($cmd.' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            if (file_exists($mysqldumpPath)) {
                $cmd = "\"{$mysqldumpPath}\" --host={$dbHost} --user={$dbUser}";
                if ($dbPass !== '') {
                    $cmd .= " --password={$dbPass}";
                }
                $cmd .= " {$dbName} > \"{$sqlFilePath}\"";
                exec($cmd.' 2>&1', $output, $returnVar);
            }
        }

        if ($returnVar !== 0) {
            abort(500, "Error al realizar backup de la base de datos '{$dbName}'. Verifique que la BD existe y las credenciales son correctas. Detalle: ".implode("\n", $output));
        }

        // --- Backup de archivos (ZIP) ---
        $zipFileName = "backup_files_{$timestamp}.zip";
        $zipFilePath = $backupDir.DIRECTORY_SEPARATOR.$zipFileName;

        $zip = new \ZipArchive;
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Error al crear el archivo ZIP.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            $filePath = $file->getRealPath();
            $relativePath = str_replace('\\', '/', substr($filePath, strlen($projectDir) + 1));

            foreach (self::EXCLUIR_PREFIJOS as $prefijo) {
                if (str_starts_with($relativePath, $prefijo.'/')) {
                    continue 2;
                }
            }

            $zip->addFile($filePath, $relativePath);
        }
        $zip->close();

        return response()->json([
            'status' => 'success',
            'message' => '¡Copia de Seguridad Exitosa!',
            'details' => [
                'database' => $sqlFileName,
                'files' => $zipFileName,
                'location' => '/backups/',
            ],
        ]);
    }
}
