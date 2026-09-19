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
        Schema::create('viajes', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('ruta_id')->index('ruta_id');
            $table->integer('tipo_bus_id')->nullable();
            $table->integer('terminal_origen_id')->nullable();
            $table->integer('terminal_destino_id')->nullable();
            $table->integer('bus_id')->nullable()->index('bus_id');
            $table->integer('chofer_id')->nullable()->index('chofer_id');
            $table->dateTime('fecha_salida');
            $table->time('hora_salida')->nullable();
            $table->dateTime('fecha_llegada_estimada')->nullable();
            $table->time('hora_llegada')->nullable();
            $table->decimal('precio_base', 10)->nullable()->default(0);
            $table->string('tipo_servicio', 50)->nullable()->default('Ejecutivo');
            $table->text('servicios_incluidos')->nullable()->comment('JSON con servicios incluidos');
            $table->text('notas')->nullable();
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();
            // 'programado' (minuscula) se saca del ENUM: MySQL la considera duplicada de
            // 'Programado' bajo collation *_ci y rompe el CREATE TABLE en una instalacion
            // nueva. El codigo ya compara con LOWER() (ver auditoria-normalizacion-bd), asi
            // que no se pierde ningun estado realmente distinto.
            $table->enum('estado', ['Activo', 'Programado', 'Inactivo', 'abordando', 'en_ruta', 'finalizado', 'cancelado'])->nullable()->default('Programado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viajes');
    }
};
