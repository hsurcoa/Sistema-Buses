<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indice unico que impide la doble venta de un asiento a nivel de base de
 * datos (auditoria de normalizacion, hallazgo 5). Hoy la unica proteccion
 * es de aplicacion (`RutaService::registrarVentaTransaccion()`, con
 * `lockForUpdate()`); esto agrega una segunda barrera si algun dia algo
 * escribe en `boletos` sin pasar por ahi.
 *
 * MySQL/MariaDB no soportan indices unicos parciales (a diferencia de
 * Postgres): se usa el patron estandar de columna generada — NULL cuando el
 * boleto no esta activo (los NULL no chocan entre si en un indice unico), y
 * la clave del asiento solo cuando esta 'vendido'/'reservado'.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `boletos`
            ADD COLUMN `asiento_activo_clave` VARCHAR(40)
                GENERATED ALWAYS AS (
                    CASE WHEN `estado` IN ('vendido', 'reservado')
                         THEN CONCAT(`viaje_id`, '-', `numero_asiento`)
                         ELSE NULL END
                ) STORED
        ");
        DB::statement('ALTER TABLE `boletos` ADD UNIQUE INDEX `idx_boletos_asiento_activo_unico` (`asiento_activo_clave`)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `boletos` DROP INDEX `idx_boletos_asiento_activo_unico`');
        DB::statement('ALTER TABLE `boletos` DROP COLUMN `asiento_activo_clave`');
    }
};
