<?php

namespace App\Http\Controllers;

use App\Models\LibelulaCobro;
use App\Services\RutaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Callback público que Libélula invoca (GET o POST, según su manual)
 * cuando un pago se confirma: "/registrar_pago?transaction_id={id}" en su
 * ejemplo, configurable como callback_url al registrar cada deuda. Sin
 * middleware `auth` (Libélula no tiene sesión nuestra) y exento de CSRF
 * (ver App\Http\Middleware\VerifyCsrfToken::$except).
 */
class LibelulaWebhookController extends Controller
{
    public function __construct(private RutaService $rutas) {}

    public function confirmar(Request $request)
    {
        $transactionId = $request->input('transaction_id');
        if (! $transactionId) {
            return response()->json(['error' => true, 'mensaje' => 'Falta transaction_id'], 400);
        }

        $cobro = LibelulaCobro::where('id_transaccion', $transactionId)->first();
        if (! $cobro) {
            Log::warning('LibelulaWebhookController: callback con transaction_id desconocido: '.$transactionId);

            return response()->json(['error' => true, 'mensaje' => 'Transacción no reconocida'], 404);
        }

        if ($cobro->estado === 'pagado') {
            return response()->json(['error' => false, 'mensaje' => 'Ya estaba confirmado']);
        }

        $resultado = $this->rutas->confirmarPagoLibelula($cobro->boleto_id, $transactionId);

        $cobro->update([
            'estado' => $resultado['aplicado_caja'] ? 'pagado' : 'pagado_sin_aplicar',
            'fecha_pago' => now(),
        ]);

        if (! $resultado['aplicado_caja']) {
            Log::warning("LibelulaWebhookController: pago confirmado (boleto {$cobro->boleto_id}) pero no se pudo aplicar a caja: ".$resultado['mensaje']);
        }

        return response()->json(['error' => false, 'mensaje' => 'Pago procesado']);
    }
}
