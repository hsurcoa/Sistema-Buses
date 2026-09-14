<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los gastos se registraban con origen 'GASTO_OPERATIVO', que no existe en el
 * ENUM de movimientos_caja.origen_modulo: MySQL (sin modo estricto) los guardaba
 * con origen vacio. El controlador ya usa 'GASTO'; aqui se corrigen los historicos.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE movimientos_caja SET origen_modulo = 'GASTO' WHERE tipo_movimiento = 'EGRESO' AND origen_modulo = ''");
    }

    public function down(): void
    {
        // Sin reversion: el valor vacio no era un dato valido.
    }
};
