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
        Schema::create('tarifas_encomienda', function (Blueprint $table) {
            $table->collation = 'utf8mb4_general_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('ruta_id')->index('fk_tarifa_ruta');
            $table->decimal('precio_base', 10)->default(10);
            $table->decimal('precio_por_kg', 10)->default(2);
            $table->decimal('porcentaje_seguro', 5)->default(2);
            $table->boolean('estado')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifas_encomienda');
    }
};
