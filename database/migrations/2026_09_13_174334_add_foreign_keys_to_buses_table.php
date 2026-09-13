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
        Schema::table('buses', function (Blueprint $table) {
            $table->foreign(['plantilla_id'], 'buses_ibfk_1')->references(['id'])->on('plantillas_bus')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['chofer_default_id'], 'buses_ibfk_2')->references(['id'])->on('usuarios')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['tipo_bus_id'], 'fk_buses_tipo_bus')->references(['id'])->on('tipos_buses')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buses', function (Blueprint $table) {
            $table->dropForeign('buses_ibfk_1');
            $table->dropForeign('buses_ibfk_2');
            $table->dropForeign('fk_buses_tipo_bus');
        });
    }
};
