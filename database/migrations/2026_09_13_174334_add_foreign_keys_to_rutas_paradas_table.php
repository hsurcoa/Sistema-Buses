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
        Schema::table('rutas_paradas', function (Blueprint $table) {
            $table->foreign(['ruta_id'], 'rutas_paradas_ibfk_1')->references(['id'])->on('rutas')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutas_paradas', function (Blueprint $table) {
            $table->dropForeign('rutas_paradas_ibfk_1');
        });
    }
};
