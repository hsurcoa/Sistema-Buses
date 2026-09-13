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
        Schema::table('boletos', function (Blueprint $table) {
            $table->foreign(['viaje_id'], 'boletos_ibfk_1')->references(['id'])->on('viajes')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['cliente_id'], 'boletos_ibfk_2')->references(['id'])->on('clientes')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['usuario_vendedor_id'], 'boletos_ibfk_3')->references(['id'])->on('usuarios')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['sesion_caja_id'], 'fk_boletos_caja_sesion')->references(['id'])->on('cajas_sesiones')->onUpdate('restrict')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boletos', function (Blueprint $table) {
            $table->dropForeign('boletos_ibfk_1');
            $table->dropForeign('boletos_ibfk_2');
            $table->dropForeign('boletos_ibfk_3');
            $table->dropForeign('fk_boletos_caja_sesion');
        });
    }
};
