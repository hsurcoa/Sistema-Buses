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
        Schema::create('asignaciones_buses', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->integer('id', true);
            $table->integer('chofer_id')->index('chofer_id');
            $table->integer('bus_id')->index('bus_id');
            $table->integer('copiloto_id')->nullable()->index('copiloto_id');
            $table->dateTime('fecha_asignacion')->nullable()->useCurrent();
            $table->tinyInteger('estado')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_buses');
    }
};
