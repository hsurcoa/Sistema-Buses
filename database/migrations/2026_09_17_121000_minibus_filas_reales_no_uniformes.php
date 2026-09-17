<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige `2026_09_17_120000_layout_asientos_realista_por_tipo_bus.php`: esa
 * migracion solo arreglaba la POSICION del pasillo (1+2 en vez de 2+2), pero
 * seguia dibujando la MISMA cantidad de asientos en cada fila -- un minibus
 * real (tipo van/Hiace) tiene una fila delantera chica junto al chofer y una
 * banca trasera mas ancha, no filas parejas. bus-renderer.js ahora soporta
 * `configuracion_asientos.filas` (cantidad de asientos por fila) para esto.
 *
 * 14 asientos = 1 (junto al chofer) + 3 + 3 + 3 (bancas del medio) + 4 (banca
 * trasera). Ajustable en Tipos de Buses si la flota real difiere.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tipos_buses')->where('nombre', 'Minibus')->update([
            'configuracion_asientos' => json_encode([
                'columnas' => 4,
                'posicion_pasillo' => 0,
                'filas' => [1, 3, 3, 3, 4],
            ]),
        ]);
    }

    public function down(): void
    {
        DB::table('tipos_buses')->where('nombre', 'Minibus')->update([
            'configuracion_asientos' => json_encode(['columnas' => 3, 'posicion_pasillo' => 1]),
        ]);
    }
};
