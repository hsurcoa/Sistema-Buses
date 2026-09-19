<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `tipo_paquete_id` (el catalogo de precios de encomienda_tipos, ej.
 * "Caja pequeña") se usaba solo para calcular el precio al momento de
 * registrar y despues se descartaba — ninguna tabla lo guardaba. Con esto
 * queda trazable que tipo de paquete se cobro en cada envio (distinto de
 * `tipo_carga`, que es la categoria de manejo: general/fragil/etc).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalles_encomienda', function (Blueprint $table) {
            // Firmado (no unsignedInteger) a proposito: encomienda_tipos.id se
            // declaro como integer() firmado — un tipo distinto en la FK
            // (unsigned vs signed) hace que InnoDB rechace la constraint (errno 150).
            $table->integer('tipo_paquete_id')->nullable()->after('tipo_carga');
            // Sin nullOnDelete/cascade a proposito: EncomiendaService::eliminarTipoEncomienda()
            // ya bloquea el borrado si esta en uso (con un mensaje claro), asi que esta FK
            // nunca deberia disparar por un intento de borrado normal.
            $table->foreign('tipo_paquete_id')->references('id')->on('encomienda_tipos');
        });
    }

    public function down(): void
    {
        Schema::table('detalles_encomienda', function (Blueprint $table) {
            $table->dropForeign(['tipo_paquete_id']);
            $table->dropColumn('tipo_paquete_id');
        });
    }
};
