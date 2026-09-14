<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cobro con QR y metodo de pago en boletos y caja.
 *
 * - boletos.metodo_pago / referencia_pago / fecha_pago: saber como se cobro cada
 *   boleto y el numero de operacion del banco cuando es QR.
 * - movimientos_caja.metodo_pago: el arqueo compara el efectivo contado solo con
 *   los ingresos en EFECTIVO; los cobros QR van directo a la cuenta del dueño.
 * - Claves de configuracion del QR (imagen y datos del beneficiario).
 *
 * Todo lo existente queda como EFECTIVO (es lo unico que se podia registrar).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('boletos', 'metodo_pago')) {
            DB::statement("ALTER TABLE boletos
                ADD COLUMN metodo_pago VARCHAR(10) NOT NULL DEFAULT 'EFECTIVO' AFTER precio_final,
                ADD COLUMN referencia_pago VARCHAR(60) NULL AFTER metodo_pago,
                ADD COLUMN fecha_pago DATETIME NULL AFTER referencia_pago");
        }

        if (! Schema::hasColumn('movimientos_caja', 'metodo_pago')) {
            DB::statement("ALTER TABLE movimientos_caja
                ADD COLUMN metodo_pago VARCHAR(10) NOT NULL DEFAULT 'EFECTIVO' AFTER monto");
        }

        foreach ([
            'pago_qr_activo' => '0',
            'pago_qr_imagen' => '',
            'pago_qr_titular' => '',
            'pago_qr_entidad' => '',
            'pago_qr_instrucciones' => 'Escanee el código con la app de su banco o billetera y muestre el comprobante al vendedor.',
            'pago_qr_minutos' => '15',
        ] as $clave => $valor) {
            DB::statement('INSERT IGNORE INTO configuracion_sistema (clave, valor) VALUES (?, ?)', [$clave, $valor]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('movimientos_caja', 'metodo_pago')) {
            DB::statement('ALTER TABLE movimientos_caja DROP COLUMN metodo_pago');
        }
        if (Schema::hasColumn('boletos', 'metodo_pago')) {
            DB::statement('ALTER TABLE boletos DROP COLUMN metodo_pago, DROP COLUMN referencia_pago, DROP COLUMN fecha_pago');
        }
        DB::statement("DELETE FROM configuracion_sistema WHERE clave LIKE 'pago\\_qr\\_%'");
    }
};
