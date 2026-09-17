<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina la tabla `buses`: duplicaba `vehiculos` (auditoria de
 * normalizacion, hallazgo 1). Verificado antes de borrar:
 * - 0 filas (tabla nunca usada en esta instancia).
 * - Ninguna FK de otra tabla apunta a `buses.id` (viajes.bus_id y
 *   asignaciones_buses.bus_id apuntan a `vehiculos.id`, no a `buses.id`).
 * - Ningun `App\Services\*`/controlador la usa (`Vehiculo`/`VehiculoService`
 *   son los unicos que operan sobre buses reales).
 * - Tenia ademas un bug propio (`buses.chofer_default_id -> usuarios.id`
 *   en vez de `personal.id`, donde realmente viven los choferes).
 *
 * down() recrea la estructura (sin datos: no habia ninguno) por si algo
 * inesperado dependiera de ella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('buses');
    }

    public function down(): void
    {
        DB::statement("
            CREATE TABLE `buses` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `placa` varchar(20) NOT NULL,
                `numero_interno` varchar(20) DEFAULT NULL,
                `marca` varchar(50) DEFAULT NULL,
                `modelo` varchar(50) DEFAULT NULL,
                `plantilla_id` int(11) NOT NULL,
                `tipo_bus_id` int(11) DEFAULT NULL,
                `chofer_default_id` int(11) DEFAULT NULL,
                `estado` enum('activo','mantenimiento','baja') DEFAULT 'activo',
                PRIMARY KEY (`id`),
                UNIQUE KEY `placa` (`placa`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
};
