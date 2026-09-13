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
        Schema::create('rutas_paradas', function (Blueprint $table) {
            $table->collation = 'utf8mb4_general_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('ruta_id')->index('ruta_id');
            $table->string('nombre_parada', 100);
            $table->integer('orden_index');
            $table->decimal('precio_pasaje', 10)->nullable()->default(0);
            $table->decimal('precio_base_encomienda', 10)->nullable()->default(0);
            $table->boolean('estado')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutas_paradas');
    }
};
