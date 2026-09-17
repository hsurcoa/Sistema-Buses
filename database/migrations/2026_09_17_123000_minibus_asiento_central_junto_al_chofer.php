<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ultimo ajuste del usuario (conoce el vehiculo real): el chofer va a la
 * izquierda y en el asiento delantero entra un pasajero MAS al centro,
 * ademas del que va junto a la puerta -- es decir la fila 1 tambien es de 3,
 * igual que el resto. Con esto las 5 filas quedan parejas (3+3+3+3+3=15),
 * no hay fila especial: sube la capacidad de 14 a 15.
 *
 * `tipos_buses.capacidad` tiene que coincidir con la suma de `filas` (ver
 * bus-renderer.js parseLayout(): si no coincide, ignora `filas` y cae al
 * grid uniforme). Se actualiza tambien `vehiculos.asientos` de los buses de
 * este tipo para no dejar el dato inconsistente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tipoId = DB::table('tipos_buses')->where('nombre', 'Minibus')->value('id');
        if (! $tipoId) {
            return;
        }

        DB::table('tipos_buses')->where('id', $tipoId)->update([
            'capacidad' => 15,
            'configuracion_asientos' => json_encode([
                'columnas' => 3,
                'posicion_pasillo' => 0,
                'filas' => [3, 3, 3, 3, 3],
            ]),
        ]);

        DB::table('vehiculos')->where('tipo_bus_id', $tipoId)->where('asientos', 14)->update(['asientos' => 15]);
    }

    public function down(): void
    {
        $tipoId = DB::table('tipos_buses')->where('nombre', 'Minibus')->value('id');
        if (! $tipoId) {
            return;
        }

        DB::table('vehiculos')->where('tipo_bus_id', $tipoId)->where('asientos', 15)->update(['asientos' => 14]);

        DB::table('tipos_buses')->where('id', $tipoId)->update([
            'capacidad' => 14,
            'configuracion_asientos' => json_encode([
                'columnas' => 3,
                'posicion_pasillo' => 0,
                'filas' => [2, 3, 3, 3, 3],
            ]),
        ]);
    }
};
