<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Correccion de la flota (buses reales, choferes y asignaciones).
 *
 * Problemas encontrados en los datos de produccion:
 * 1. vehiculos no tenia tipo de bus: el viaje guardaba un tipo_bus_id aparte y
 *    el mapa de asientos no coincidia con el bus real (p. ej. viaje 19: bus de
 *    60 asientos con un tipo de 16; viaje 17: bus de 59 con un tipo de 50).
 * 2. viajes.chofer_id guarda ids de `personal` pero su FK apuntaba a
 *    `usuarios`, lo que obligo a crear cuentas de usuario "fantasma" para los
 *    choferes (chofer_18, chofer_22... con rol Supervisor).
 * 3. asignaciones_buses permitia varias asignaciones activas por bus y por
 *    chofer (bus his-1988 con 2 choferes; chofer Elar Alvan en 2 buses).
 * 4. Placas y marcas sin normalizar ("hsa-2635", "TOYOYA").
 *
 * Backup previo: backups/backup_db_2026-09-13_20-46_pre_flota_qr.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Tipo de bus del vehiculo (define asientos y distribucion reales)
        if (! Schema::hasColumn('vehiculos', 'tipo_bus_id')) {
            DB::statement('ALTER TABLE vehiculos ADD COLUMN tipo_bus_id INT(11) NULL AFTER placa');
            DB::statement('ALTER TABLE vehiculos ADD CONSTRAINT vehiculos_tipo_bus_fk FOREIGN KEY (tipo_bus_id) REFERENCES tipos_buses(id) ON DELETE SET NULL');
        }

        // Vinculo automatico SOLO cuando hay un unico tipo con exactamente la
        // misma cantidad de asientos. Los ambiguos o sin tipo quedan en NULL y
        // se muestran como "revisar" en Gestion de Flota.
        DB::statement("
            UPDATE vehiculos v
            JOIN (
                SELECT capacidad, MIN(id) AS tipo_id
                FROM tipos_buses
                GROUP BY capacidad
                HAVING COUNT(*) = 1
            ) t ON t.capacidad = v.asientos
            SET v.tipo_bus_id = t.tipo_id
            WHERE v.tipo_bus_id IS NULL
        ");

        // 4. Normalizacion de placa y marca
        DB::statement("UPDATE vehiculos SET placa = UPPER(REPLACE(TRIM(placa), ' ', ''))");
        DB::statement("UPDATE vehiculos SET marca = 'Toyota' WHERE UPPER(TRIM(marca)) IN ('TOYOTA', 'TOYOYA')");
        DB::statement("UPDATE vehiculos SET marca = 'Marcopolo' WHERE UPPER(TRIM(marca)) = 'MARCOPOLO'");

        // 2. viajes.chofer_id -> personal
        // Primero se quita la FK vieja (a usuarios): si no, no se pueden escribir ids de personal.
        // viajes.estado era un ENUM con 'Programado' y 'programado' duplicados:
        // MariaDB rechaza cualquier ALTER sobre la tabla mientras exista. Se pasa a
        // VARCHAR conservando los valores (la comparacion SQL no distingue mayusculas).
        if (DB::selectOne("SELECT DATA_TYPE AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'viajes' AND COLUMN_NAME = 'estado'")->t === 'enum') {
            DB::statement("ALTER TABLE viajes MODIFY estado VARCHAR(20) NULL DEFAULT 'Programado'");
        }
        if ($this->fkExists('viajes', 'viajes_ibfk_3')) {
            DB::statement('ALTER TABLE viajes DROP FOREIGN KEY viajes_ibfk_3');
        }
        // Ids que no existen en personal: se traducen por numero de documento
        // desde la cuenta de usuario (p. ej. usuario 10 Robinson Alvan -> personal 21).
        DB::statement("
            UPDATE viajes v
            JOIN usuarios u ON u.id = v.chofer_id
            JOIN personal p ON p.numero_documento COLLATE utf8mb4_unicode_ci
                IN (u.nro_documento COLLATE utf8mb4_unicode_ci, u.username COLLATE utf8mb4_unicode_ci)
            SET v.chofer_id = p.id
            WHERE v.chofer_id IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM personal px WHERE px.id = v.chofer_id)
        ");
        // Lo que aun no exista en personal no puede cumplir la FK nueva
        DB::statement('UPDATE viajes SET chofer_id = NULL WHERE chofer_id IS NOT NULL AND chofer_id NOT IN (SELECT id FROM personal)');

        if (! $this->fkExists('viajes', 'viajes_chofer_personal_fk')) {
            DB::statement('ALTER TABLE viajes ADD CONSTRAINT viajes_chofer_personal_fk FOREIGN KEY (chofer_id) REFERENCES personal(id) ON DELETE SET NULL');
        }

        // 3. Una sola asignacion activa por bus y por chofer: se conserva la mas reciente
        DB::statement('
            UPDATE asignaciones_buses a
            JOIN asignaciones_buses b ON b.bus_id = a.bus_id AND b.estado = 1 AND b.id > a.id
            SET a.estado = 0
            WHERE a.estado = 1
        ');
        DB::statement('
            UPDATE asignaciones_buses a
            JOIN asignaciones_buses b ON b.chofer_id = a.chofer_id AND b.estado = 1 AND b.id > a.id
            SET a.estado = 0
            WHERE a.estado = 1
        ');

        // 1b. Viajes aun no realizados: usar la distribucion del bus real, solo si
        // ningun asiento ya vendido/reservado queda fuera de la nueva capacidad.
        DB::statement("
            UPDATE viajes vi
            JOIN vehiculos ve ON ve.id = vi.bus_id AND ve.tipo_bus_id IS NOT NULL
            JOIN tipos_buses tb ON tb.id = ve.tipo_bus_id
            SET vi.tipo_bus_id = ve.tipo_bus_id
            WHERE LOWER(vi.estado) NOT IN ('finalizado', 'cancelado', 'inactivo')
              AND (vi.tipo_bus_id IS NULL OR vi.tipo_bus_id <> ve.tipo_bus_id)
              AND tb.capacidad >= COALESCE((
                  SELECT MAX(b.numero_asiento) FROM boletos b
                  WHERE b.viaje_id = vi.id AND b.estado IN ('vendido', 'reservado')
              ), 0)
        ");
    }

    public function down(): void
    {
        if ($this->fkExists('viajes', 'viajes_chofer_personal_fk')) {
            DB::statement('ALTER TABLE viajes DROP FOREIGN KEY viajes_chofer_personal_fk');
        }
        // La FK original a usuarios solo se puede restaurar si los ids existen alli
        DB::statement('UPDATE viajes SET chofer_id = NULL WHERE chofer_id IS NOT NULL AND chofer_id NOT IN (SELECT id FROM usuarios)');
        if (! $this->fkExists('viajes', 'viajes_ibfk_3')) {
            DB::statement('ALTER TABLE viajes ADD CONSTRAINT viajes_ibfk_3 FOREIGN KEY (chofer_id) REFERENCES usuarios(id)');
        }

        if (Schema::hasColumn('vehiculos', 'tipo_bus_id')) {
            DB::statement('ALTER TABLE vehiculos DROP FOREIGN KEY vehiculos_tipo_bus_fk');
            DB::statement('ALTER TABLE vehiculos DROP COLUMN tipo_bus_id');
        }
        // Normalizaciones de datos y asignaciones desactivadas: restaurar desde el backup si hiciera falta.
    }

    private function fkExists(string $table, string $name): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
