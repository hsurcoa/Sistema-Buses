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
        Schema::table('viajes', function (Blueprint $table) {
            $table->foreign(['ruta_id'], 'viajes_ibfk_1')->references(['id'])->on('rutas')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['bus_id'], 'viajes_ibfk_2')->references(['id'])->on('vehiculos')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['chofer_id'], 'viajes_ibfk_3')->references(['id'])->on('usuarios')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropForeign('viajes_ibfk_1');
            $table->dropForeign('viajes_ibfk_2');
            $table->dropForeign('viajes_ibfk_3');
        });
    }
};
