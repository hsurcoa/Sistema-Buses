<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multisucursal. Decisiones del dueño (14/09/2026):
 * - Cada terminal es una sucursal con su propia caja.
 * - Cada vendedor trabaja en una sola sucursal (Administrador/Supervisor pueden operar en cualquiera).
 * - Cada sucursal puede tener su propio QR de cobro; si no, se usa el QR general.
 *
 * Cambios:
 * - terminales: telefono, prefijo y correlativos (boletos y guias), QR propio.
 * - usuarios, cajas_sesiones, boletos: sucursal_id.
 * - encomiendas: sucursal de origen y destino.
 *
 * Datos historicos (aproximacion documentada): la caja se asigna a la terminal de
 * salida de la mayoria de sus boletos; el boleto a la sucursal de su caja o, sin
 * caja, a la terminal de salida del viaje.
 * Backup previo: backups/backup_db_2026-09-14_05-38_pre_multisucursal.sql
 */
return new class extends Migration
{
    private const PREFIJOS = [
        'La Paz - Central' => 'LPZ', 'El Alto' => 'EAL', 'Copacabana' => 'COP', 'Laja' => 'LAJ',
        'Ancoraimes' => 'ANC', 'Pto. Nicolas Acosta' => 'PAC', 'Puerto Chaguaya' => 'CHA', 'Puerto Carabuco' => 'CAR',
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('terminales', 'prefijo_boleto')) {
            DB::statement("ALTER TABLE terminales
                ADD COLUMN telefono VARCHAR(30) NULL AFTER numero_oficina,
                ADD COLUMN prefijo_boleto VARCHAR(6) NULL AFTER telefono,
                ADD COLUMN correlativo_boleto INT NOT NULL DEFAULT 0 AFTER prefijo_boleto,
                ADD COLUMN correlativo_guia INT NOT NULL DEFAULT 0 AFTER correlativo_boleto,
                ADD COLUMN pago_qr_imagen VARCHAR(255) NULL,
                ADD COLUMN pago_qr_titular VARCHAR(120) NULL,
                ADD COLUMN pago_qr_entidad VARCHAR(120) NULL");
        }

        foreach (DB::select('SELECT id, nombre_sede FROM terminales WHERE prefijo_boleto IS NULL') as $t) {
            $prefijo = self::PREFIJOS[$t->nombre_sede] ?? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $t->nombre_sede), 0, 3));
            // evitar prefijos repetidos
            $base = $prefijo;
            $n = 1;
            while (DB::table('terminales')->where('prefijo_boleto', $prefijo)->exists()) {
                $prefijo = substr($base, 0, 2) . $n++;
            }
            DB::table('terminales')->where('id', $t->id)->update(['prefijo_boleto' => $prefijo]);
        }
        if (! $this->indexExists('terminales', 'uk_terminales_prefijo')) {
            DB::statement('ALTER TABLE terminales ADD UNIQUE KEY uk_terminales_prefijo (prefijo_boleto)');
        }

        $this->addSucursalColumn('usuarios', 'sucursal_id', 'rol_id');
        $this->addSucursalColumn('cajas_sesiones', 'sucursal_id', 'usuario_id');
        $this->addSucursalColumn('boletos', 'sucursal_id', 'sesion_caja_id');
        $this->addSucursalColumn('encomiendas', 'sucursal_origen_id', 'viaje_id');
        $this->addSucursalColumn('encomiendas', 'sucursal_destino_id', 'sucursal_origen_id');

        // Usuarios: sucursal escrita a mano en texto libre
        DB::statement("UPDATE usuarios SET sucursal_id = (SELECT id FROM terminales WHERE nombre_sede = 'La Paz - Central')
                       WHERE sucursal_id IS NULL AND sucursal_asignada IN ('La Paz', 'Central')");

        // Cajas historicas: terminal de salida de la mayoria de sus boletos; sin boletos, la del usuario
        DB::statement("
            UPDATE cajas_sesiones c
            SET c.sucursal_id = COALESCE(
                (SELECT v.terminal_origen_id FROM boletos b JOIN viajes v ON v.id = b.viaje_id
                 WHERE b.sesion_caja_id = c.id AND v.terminal_origen_id IS NOT NULL
                 GROUP BY v.terminal_origen_id ORDER BY COUNT(*) DESC LIMIT 1),
                (SELECT u.sucursal_id FROM usuarios u WHERE u.id = c.usuario_id)
            )
            WHERE c.sucursal_id IS NULL
        ");

        DB::statement("
            UPDATE boletos b
            LEFT JOIN cajas_sesiones c ON c.id = b.sesion_caja_id
            JOIN viajes v ON v.id = b.viaje_id
            SET b.sucursal_id = COALESCE(c.sucursal_id, v.terminal_origen_id)
            WHERE b.sucursal_id IS NULL
        ");

        DB::statement("
            UPDATE encomiendas e
            JOIN viajes v ON v.id = e.viaje_id
            SET e.sucursal_origen_id = COALESCE(e.sucursal_origen_id, v.terminal_origen_id),
                e.sucursal_destino_id = COALESCE(e.sucursal_destino_id, v.terminal_destino_id)
        ");
    }

    public function down(): void
    {
        foreach ([['encomiendas', 'sucursal_destino_id'], ['encomiendas', 'sucursal_origen_id'], ['boletos', 'sucursal_id'], ['cajas_sesiones', 'sucursal_id'], ['usuarios', 'sucursal_id']] as [$tabla, $col]) {
            if (Schema::hasColumn($tabla, $col)) {
                DB::statement("ALTER TABLE {$tabla} DROP FOREIGN KEY fk_{$tabla}_{$col}");
                DB::statement("ALTER TABLE {$tabla} DROP COLUMN {$col}");
            }
        }
        if (Schema::hasColumn('terminales', 'prefijo_boleto')) {
            DB::statement('ALTER TABLE terminales DROP INDEX uk_terminales_prefijo');
            DB::statement('ALTER TABLE terminales DROP COLUMN telefono, DROP COLUMN prefijo_boleto, DROP COLUMN correlativo_boleto,
                DROP COLUMN correlativo_guia, DROP COLUMN pago_qr_imagen, DROP COLUMN pago_qr_titular, DROP COLUMN pago_qr_entidad');
        }
    }

    private function addSucursalColumn(string $tabla, string $columna, string $despuesDe): void
    {
        if (Schema::hasColumn($tabla, $columna)) {
            return;
        }
        DB::statement("ALTER TABLE {$tabla} ADD COLUMN {$columna} INT(11) NULL AFTER {$despuesDe},
            ADD KEY idx_{$tabla}_{$columna} ({$columna}),
            ADD CONSTRAINT fk_{$tabla}_{$columna} FOREIGN KEY ({$columna}) REFERENCES terminales(id) ON DELETE SET NULL");
    }

    private function indexExists(string $tabla, string $indice): bool
    {
        return (bool) DB::selectOne('SELECT 1 AS x FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1', [$tabla, $indice]);
    }
};
