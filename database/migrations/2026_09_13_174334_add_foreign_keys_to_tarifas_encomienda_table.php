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
        Schema::table('tarifas_encomienda', function (Blueprint $table) {
            $table->foreign(['ruta_id'], 'fk_tarifa_ruta')->references(['id'])->on('rutas')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarifas_encomienda', function (Blueprint $table) {
            $table->dropForeign('fk_tarifa_ruta');
        });
    }
};
