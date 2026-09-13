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
        Schema::table('series_boletos', function (Blueprint $table) {
            $table->foreign(['usuario_id'], 'fk_series_personal')->references(['id'])->on('personal')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['sede_id'], 'fk_series_terminales')->references(['id'])->on('terminales')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series_boletos', function (Blueprint $table) {
            $table->dropForeign('fk_series_personal');
            $table->dropForeign('fk_series_terminales');
        });
    }
};
