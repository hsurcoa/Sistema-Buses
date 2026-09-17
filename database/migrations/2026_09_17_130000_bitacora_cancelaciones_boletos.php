<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitacora de cancelaciones de boletos (pedido del usuario): hasta ahora
 * cancelar un boleto vendido revertia la plata en caja automaticamente sin
 * preguntar (ver RutaService::cancelarBoleto), y no quedaba registro de si
 * realmente se le devolvio el dinero al pasajero. Esta tabla registra TODA
 * cancelacion de un boleto ya pagado -con o sin devolucion- para que quede
 * trazable quien la hizo, por que, y si hubo plata de por medio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancelaciones_boletos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('boleto_id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('sesion_caja_id')->nullable();
            $table->decimal('monto', 10, 2);
            $table->boolean('devuelto')->default(false);
            $table->string('metodo_devolucion', 10)->nullable();
            $table->string('motivo', 255)->nullable();
            // La devolucion puede procesarse en el momento de la cancelacion
            // o despues (el pasajero vuelve a reclamar la plata): estos dos
            // campos quedan null hasta que efectivamente se le devuelve.
            $table->unsignedInteger('usuario_devolucion_id')->nullable();
            $table->timestamp('fecha_devolucion')->nullable();
            $table->timestamp('fecha_creacion')->useCurrent();

            $table->index('boleto_id');
            $table->index('fecha_creacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancelaciones_boletos');
    }
};
