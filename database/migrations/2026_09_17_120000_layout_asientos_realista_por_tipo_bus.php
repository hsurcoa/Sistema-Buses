<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El mapa de asientos (public/js/bus-renderer.js -> parseLayout()) usa
 * `tipos_buses.configuracion_asientos` (JSON: columnas, posicion_pasillo)
 * para dibujar filas/pasillo. Sin ese dato cae en un fallback fijo de
 * 4 columnas (2+2) -- correcto para un bus grande, pero un minibus real
 * (tipo van/Hiace) va con un asiento individual de un lado del pasillo,
 * no 2+2. Se corrige solo para tipos que ya no tengan configuracion propia
 * (no pisa lo que el usuario haya editado a mano en Tipos de Buses).
 */
return new class extends Migration
{
    public function up(): void
    {
        $tipos = [
            // Minibus/van: 1 asiento + pasillo + 2 asientos por fila.
            'Minibus' => ['columnas' => 3, 'posicion_pasillo' => 1],
            // Bus grande de 2 pisos: 2 + pasillo + 2, igual que el fallback previo.
            'Bus Dos Pisos' => ['columnas' => 4, 'posicion_pasillo' => 2],
        ];

        foreach ($tipos as $nombre => $config) {
            DB::table('tipos_buses')
                ->where('nombre', $nombre)
                ->where(function ($q) {
                    $q->whereNull('configuracion_asientos')->orWhere('configuracion_asientos', '');
                })
                ->update(['configuracion_asientos' => json_encode($config)]);
        }
    }

    public function down(): void
    {
        DB::table('tipos_buses')
            ->whereIn('nombre', ['Minibus', 'Bus Dos Pisos'])
            ->update(['configuracion_asientos' => null]);
    }
};
