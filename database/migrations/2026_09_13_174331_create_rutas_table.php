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
        Schema::create('rutas', function (Blueprint $table) {
            $table->collation = 'utf8mb4_general_ci';
            $table->charset = 'utf8mb4';

            $table->comment('Tabla de rutas de transporte');
            $table->integer('id', true);
            $table->string('origen', 100)->comment('Ciudad de origen');
            $table->string('destino', 100)->comment('Ciudad de destino');
            $table->boolean('estado')->nullable()->default(true)->comment('1=Activo, 0=Inactivo');
            $table->dateTime('fecha_creacion')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
