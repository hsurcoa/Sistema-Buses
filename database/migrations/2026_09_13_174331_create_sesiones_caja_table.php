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
        Schema::create('sesiones_caja', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('usuario_id')->index('usuario_id');
            $table->dateTime('fecha_apertura')->nullable()->useCurrent();
            $table->dateTime('fecha_cierre')->nullable();
            $table->decimal('monto_inicial', 10);
            $table->decimal('monto_final_sistema', 10)->nullable()->default(0);
            $table->decimal('monto_final_real', 10)->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->nullable()->default('abierta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_caja');
    }
};
