<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SOAT e ITV (Inspeccion Tecnica Vehicular) son los dos documentos que
 * realmente pide un control de transito en Bolivia y que el sistema no
 * registraba en ningun lado (pedido del usuario tras revisar el formulario
 * de Registrar Buses). Columnas nullable: no afecta datos existentes ni
 * hace obligatorio cargarlas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('soat_numero', 50)->nullable()->after('tarjeta_circulacion');
            $table->date('soat_vencimiento')->nullable()->after('soat_numero');
            $table->string('itv_numero', 50)->nullable()->after('soat_vencimiento');
            $table->date('itv_vencimiento')->nullable()->after('itv_numero');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->dropColumn(['soat_numero', 'soat_vencimiento', 'itv_numero', 'itv_vencimiento']);
        });
    }
};
