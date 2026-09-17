<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige la colacion mixta detectada en la auditoria de normalizacion
 * (docs/superpowers/plans/2026-09-17-auditoria-normalizacion-bd.md, hallazgo 4):
 * 12 tablas quedaron en utf8mb4_general_ci mientras el resto de la BD (y las
 * tablas con las que se cruzan en JOINs) esta en utf8mb4_unicode_ci, lo que
 * puede producir errores "Illegal mix of collations" o diferencias
 * silenciosas de orden/mayusculas.
 */
return new class extends Migration
{
    private const TABLAS = [
        'detalles_encomienda', 'encomienda_tipos', 'encomiendas', 'personal',
        'rutas', 'rutas_paradas', 'series_boletos', 'tarifas_encomienda',
        'tarifas_tramo', 'terminales', 'vehiculos',
    ];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            DB::statement("ALTER TABLE `{$tabla}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            DB::statement("ALTER TABLE `{$tabla}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        }
    }
};
