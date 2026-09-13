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
        Schema::create('detalles_encomienda', function (Blueprint $table) {
            $table->collation = 'utf8mb4_general_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('encomienda_id')->index('fk_detalle_encomienda');
            $table->text('descripcion');
            $table->decimal('peso_kg', 10);
            $table->enum('tipo_carga', ['GENERAL', 'DOCUMENTOS', 'FRAGIL', 'ELECTRONICA', 'PERECIBLE'])->nullable()->default('GENERAL');
            $table->decimal('valor_declarado', 10)->nullable()->default(0);
            $table->decimal('precio_calculado', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_encomienda');
    }
};
