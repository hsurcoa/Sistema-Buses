<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Venta por tramo (decision del dueño: se venden tramos intermedios).
 *
 * - tarifas_tramo: precio de cada par (sube en, baja en) por ruta.
 *   desde_parada_id = 0 significa el ORIGEN de la ruta y hasta_parada_id = 0
 *   el DESTINO FINAL (asi el par es unico sin usar NULL).
 * - boletos.parada_subida_id: donde sube el pasajero (NULL = origen). La bajada
 *   ya existia en boletos.parada_id (NULL = destino final).
 *
 * Precarga de tarifas desde los precios actuales:
 * - origen -> parada: precio_pasaje de la parada
 * - origen -> destino: precio base del ultimo viaje de la ruta
 * - tramos intermedios: diferencia de precios; si sale <= 0 (datos incoherentes,
 *   p. ej. una parada mas cara que el destino final) se usa la tarifa mas baja
 *   de la ruta como piso y queda marcado para revisar en el editor.
 * Backup previo: backups/backup_db_2026-09-14_05-59_pre_tramos.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tarifas_tramo')) {
            DB::statement("CREATE TABLE tarifas_tramo (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                ruta_id INT(11) NOT NULL,
                desde_parada_id INT(11) NOT NULL DEFAULT 0 COMMENT '0 = origen de la ruta',
                hasta_parada_id INT(11) NOT NULL DEFAULT 0 COMMENT '0 = destino final',
                precio DECIMAL(10,2) NOT NULL,
                precio_sugerido TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = calculado al migrar, revisar',
                actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_tarifa_tramo (ruta_id, desde_parada_id, hasta_parada_id),
                CONSTRAINT fk_tarifas_tramo_ruta FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        if (! Schema::hasColumn('boletos', 'parada_subida_id')) {
            DB::statement('ALTER TABLE boletos ADD COLUMN parada_subida_id INT(11) NULL AFTER sucursal_id');
        }

        foreach (DB::select('SELECT id FROM rutas') as $ruta) {
            if (DB::table('tarifas_tramo')->where('ruta_id', $ruta->id)->exists()) {
                continue;
            }

            $paradas = DB::select('SELECT id, precio_pasaje FROM rutas_paradas WHERE ruta_id = ? AND estado = 1 ORDER BY orden_index, id', [$ruta->id]);
            $precioDestino = DB::table('viajes')->where('ruta_id', $ruta->id)->orderByDesc('fecha_salida')->value('precio_base');

            // puntos en orden: origen (0), paradas, destino (0 en la columna hasta)
            $puntos = [['id' => 0, 'precio' => 0.0]];
            foreach ($paradas as $p) {
                $puntos[] = ['id' => (int) $p->id, 'precio' => (float) $p->precio_pasaje];
            }
            $destino = ['id' => 0, 'precio' => (float) ($precioDestino ?? max(array_column($puntos, 'precio')))];

            $positivos = array_filter(array_merge(array_column(array_slice($puntos, 1), 'precio'), [$destino['precio']]), fn($x) => $x > 0);
            $piso = $positivos ? min($positivos) : 0;

            $filas = [];
            $n = count($puntos);
            for ($i = 0; $i < $n; $i++) {
                $destinos = array_merge(array_slice($puntos, $i + 1), [$destino]);
                foreach ($destinos as $k => $hasta) {
                    $esDestino = ($k === count($destinos) - 1);
                    $bruto = $hasta['precio'] - $puntos[$i]['precio'];
                    $sugerido = ($i > 0 && $bruto <= 0) || ($i === 0 && $hasta['precio'] <= 0);
                    $filas[] = [
                        'ruta_id' => $ruta->id,
                        'desde_parada_id' => $puntos[$i]['id'],
                        'hasta_parada_id' => $esDestino ? 0 : $hasta['id'],
                        'precio' => round(max($bruto, $piso), 2),
                        'precio_sugerido' => ($i > 0 || $sugerido) ? 1 : 0,
                    ];
                }
            }
            DB::table('tarifas_tramo')->insert($filas);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('boletos', 'parada_subida_id')) {
            DB::statement('ALTER TABLE boletos DROP COLUMN parada_subida_id');
        }
        DB::statement('DROP TABLE IF EXISTS tarifas_tramo');
    }
};
