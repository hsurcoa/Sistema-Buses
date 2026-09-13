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
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('sesion_id')->index('sesion_id');
            $table->enum('tipo_movimiento', ['INGRESO', 'EGRESO']);
            $table->enum('origen_modulo', ['PASAJE', 'ENCOMIENDA', 'GASTO', 'APERTURA']);
            $table->integer('referencia_id')->nullable();
            $table->decimal('monto', 10);
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_creacion')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
