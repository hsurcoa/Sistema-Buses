<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige otra vez `configuracion_asientos` del Minibus segun conocimiento
 * real del usuario de minibuses bolivianos (no una suposicion generica de
 * Hiace/Sunray):
 * - Junto al chofer va DOBLE (asiento normal + central), no uno solo.
 * - Las filas de atras son PAREJAS de 3 hasta el fondo, no una banca ancha
 *   de 4 al final (eso era una suposicion incorrecta de la migracion previa
 *   2026_09_17_121000).
 *
 * 14 = 2 (junto al chofer) + 3+3+3+3 (4 filas parejas).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tipos_buses')->where('nombre', 'Minibus')->update([
            'configuracion_asientos' => json_encode([
                'columnas' => 3,
                'posicion_pasillo' => 0,
                'filas' => [2, 3, 3, 3, 3],
            ]),
        ]);
    }

    public function down(): void
    {
        DB::table('tipos_buses')->where('nombre', 'Minibus')->update([
            'configuracion_asientos' => json_encode([
                'columnas' => 4,
                'posicion_pasillo' => 0,
                'filas' => [1, 3, 3, 3, 4],
            ]),
        ]);
    }
};
