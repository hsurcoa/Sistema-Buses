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
        Schema::create('asientos_plantilla', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('plantilla_id')->index('plantilla_id');
            $table->integer('numero_asiento');
            $table->tinyInteger('piso')->nullable()->default(1);
            $table->integer('columna');
            $table->integer('fila');
            $table->enum('tipo', ['cama', 'semicama', 'normal'])->nullable()->default('normal');
            $table->enum('estado_defecto', ['libre', 'no_disponible'])->nullable()->default('libre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asientos_plantilla');
    }
};
