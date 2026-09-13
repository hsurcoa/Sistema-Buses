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
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->collation = 'utf8mb4_general_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->string('propietario_nombres', 100)->nullable();
            $table->string('propietario_apellidos', 100)->nullable();
            $table->string('tarjeta_circulacion', 50);
            $table->string('placa', 20)->unique('placa');
            $table->string('clase', 50)->nullable();
            $table->string('marca', 50)->nullable();
            $table->integer('anio')->nullable();
            $table->string('modelo', 50)->nullable();
            $table->string('tipo_combustible', 50)->nullable();
            $table->string('carroceria', 50)->nullable();
            $table->integer('ejes')->nullable();
            $table->string('color', 30)->nullable();
            $table->string('nro_motor', 50)->nullable();
            $table->integer('cilindros')->nullable();
            $table->string('nro_serie', 50)->nullable();
            $table->integer('ruedas')->nullable();
            $table->decimal('peso_seco', 10)->nullable();
            $table->decimal('peso_bruto', 10)->nullable();
            $table->decimal('longitud', 10)->nullable();
            $table->decimal('altura', 10)->nullable();
            $table->decimal('ancho', 10)->nullable();
            $table->integer('pasajeros')->nullable();
            $table->integer('asientos')->nullable();
            $table->string('tipo_servicio', 50)->nullable();
            $table->boolean('estado')->nullable()->default(true)->comment('1=activo, 0=inactivo');
            $table->dateTime('fecha_registro')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
