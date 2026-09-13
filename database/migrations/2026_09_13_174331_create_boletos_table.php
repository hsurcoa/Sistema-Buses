<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('boletos', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('viaje_id')->index('viaje_id');
            $table->integer('cliente_id')->index('cliente_id');
            $table->integer('usuario_vendedor_id')->index('usuario_vendedor_id');
            $table->integer('sesion_caja_id')->nullable()->index('sesion_caja_id');
            $table->integer('parada_id')->nullable();
            $table->integer('numero_asiento');
            $table->decimal('precio_final', 10);
            $table->enum('estado', ['reservado', 'vendido', 'cancelado', 'abordado'])->nullable()->default('reservado');
            $table->dateTime('fecha_reserva')->nullable()->useCurrent();
            $table->dateTime('fecha_expiracion_reserva')->nullable();
            $table->string('codigo_boleto', 20)->nullable()->unique('codigo_boleto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boletos');
    }
};
