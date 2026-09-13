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
        Schema::create('buses', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->string('placa', 20)->unique('placa');
            $table->string('numero_interno', 20)->nullable();
            $table->string('marca', 50)->nullable();
            $table->string('modelo', 50)->nullable();
            $table->integer('plantilla_id')->index('plantilla_id');
            $table->integer('tipo_bus_id')->nullable()->index('fk_buses_tipo_bus');
            $table->integer('chofer_default_id')->nullable()->index('chofer_default_id');
            $table->enum('estado', ['activo', 'mantenimiento', 'baja'])->nullable()->default('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
