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
        Schema::table('distritos', function (Blueprint $table) {
            $table->foreign(['id_provincia'], 'distritos_ibfk_1')->references(['id_provincia'])->on('provincias')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distritos', function (Blueprint $table) {
            $table->dropForeign('distritos_ibfk_1');
        });
    }
};
