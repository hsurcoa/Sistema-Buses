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
        Schema::create('encomiendas', function (Blueprint $table) {
            $table->collation = 'utf8mb4_general_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->string('codigo_guia', 20)->unique('uk_codigo_guia');
            $table->integer('viaje_id')->index('fk_encomienda_viaje');
            $table->integer('remitente_id')->nullable();
            $table->string('remitente_nombre', 150);
            $table->string('remitente_dni', 20);
            $table->integer('destinatario_id')->nullable();
            $table->string('destinatario_nombre', 150);
            $table->string('destinatario_dni', 20)->nullable();
            $table->string('destinatario_telefono', 20);
            $table->string('clave_retiro', 4)->nullable();
            $table->integer('usuario_creacion_id')->index('fk_encomienda_usuario');
            $table->dateTime('fecha_registro')->nullable()->useCurrent();
            $table->decimal('total_pagar', 10);
            $table->enum('estado_pago', ['PAGADO', 'PENDIENTE'])->nullable()->default('PAGADO');
            $table->enum('estado', ['REGISTRADO', 'EN_ALMACEN_ORIGEN', 'EN_RUTA', 'EN_DESTINO', 'ENTREGADO'])->nullable()->default('REGISTRADO');
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encomiendas');
    }
};
