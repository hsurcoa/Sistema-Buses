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
        Schema::table('asignaciones_buses', function (Blueprint $table) {
            $table->foreign(['chofer_id'], 'asignaciones_buses_ibfk_1')->references(['id'])->on('personal')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['bus_id'], 'asignaciones_buses_ibfk_2')->references(['id'])->on('vehiculos')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['copiloto_id'], 'asignaciones_buses_ibfk_3')->references(['id'])->on('personal')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_buses', function (Blueprint $table) {
            $table->dropForeign('asignaciones_buses_ibfk_1');
            $table->dropForeign('asignaciones_buses_ibfk_2');
            $table->dropForeign('asignaciones_buses_ibfk_3');
        });
    }
};
