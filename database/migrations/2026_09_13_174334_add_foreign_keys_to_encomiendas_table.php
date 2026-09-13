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
        Schema::table('encomiendas', function (Blueprint $table) {
            $table->foreign(['usuario_creacion_id'], 'fk_encomienda_usuario')->references(['id'])->on('usuarios')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['viaje_id'], 'fk_encomienda_viaje')->references(['id'])->on('viajes')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encomiendas', function (Blueprint $table) {
            $table->dropForeign('fk_encomienda_usuario');
            $table->dropForeign('fk_encomienda_viaje');
        });
    }
};
