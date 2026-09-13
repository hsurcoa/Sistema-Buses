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
        Schema::table('asientos_plantilla', function (Blueprint $table) {
            $table->foreign(['plantilla_id'], 'asientos_plantilla_ibfk_1')->references(['id'])->on('plantillas_bus')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asientos_plantilla', function (Blueprint $table) {
            $table->dropForeign('asientos_plantilla_ibfk_1');
        });
    }
};
