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
        Schema::table('detalles_encomienda', function (Blueprint $table) {
            $table->foreign(['encomienda_id'], 'fk_detalle_encomienda')->references(['id'])->on('encomiendas')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalles_encomienda', function (Blueprint $table) {
            $table->dropForeign('fk_detalle_encomienda');
        });
    }
};
