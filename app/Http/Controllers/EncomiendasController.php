<?php

namespace App\Http\Controllers;

use App\Services\EncomiendaService;
use App\Services\RutaService;
use Illuminate\Http\Request;

/**
 * Encomiendas: registro, cotización dinámica por tramo (reutiliza
 * `RutaService::calcularPrecioDinamico`) y recibo. Puerto de
 * `legacy/app/controllers/Encomiendas.php` (Fase 6; reescrito a Eloquent al
 * cerrar la sesion).
 */
class EncomiendasController extends Controller
{
    public function __construct(private EncomiendaService $encomiendas, private RutaService $rutas) {}

    public function index()
    {
        return view('encomiendas.index', ['data' => [
            'encomiendas' => $this->encomiendas->listarEncomiendas(),
        ]]);
    }

    public function crear()
    {
        return view('encomiendas.crear', ['data' => [
            'rutas' => $this->rutas->listarRutasActivas(),
        ]]);
    }

    public function obtenerViajes(int $rutaId)
    {
        return response()->json($this->encomiendas->listarViajesFuturosPorRuta($rutaId));
    }

    public function guardar(Request $request)
    {
        // El legacy sanitizaba TODO $_POST con FILTER_SANITIZE_SPECIAL_CHARS
        // antes de leerlo (las vistas, p. ej. recibo.php, hacen echo directo
        // sin escapar) — se replica campo por campo para no introducir un
        // XSS almacenado que el legacy no tenia.
        $post = fn (string $key, string $default = '') => htmlspecialchars(trim((string) $request->input($key, $default)), ENT_QUOTES);

        $paradaId = (int) $post('parada_id');
        $tipoPaqueteId = (int) $post('tipo_paquete_id');

        $calculo = $this->rutas->calcularPrecioDinamico($paradaId, 'encomienda', $tipoPaqueteId ?: null);

        if (! $calculo['status']) {
            abort(400, 'Error al calcular precio: '.$calculo['message']);
        }

        $datos = [
            'viaje_id' => $post('viaje_id'),
            'remitente_nombre' => $post('remitente_nombre'),
            'remitente_dni' => $post('remitente_dni'),
            'destinatario_nombre' => $post('destinatario_nombre'),
            'destinatario_dni' => $post('destinatario_dni'),
            'destinatario_telefono' => $post('destinatario_telefono'),
            'descripcion' => $post('descripcion').' [Destino: '.$calculo['desglose'].']',
            'peso' => $post('peso'),
            'tipo_carga' => $post('tipo_carga'),
            'valor_declarado' => $post('valor_declarado'),
            'clave_retiro' => $post('clave_retiro'),
            'usuario_id' => $request->user()->id,
            'precio_verificado' => $calculo['precio_total'],
            'parada_id' => $paradaId,
            'tipo_paquete_id' => $tipoPaqueteId,
        ];

        $resultado = $this->encomiendas->registrarEncomienda($datos);

        if (! $resultado['status']) {
            abort(400, 'Error al guardar: '.$resultado['message']);
        }

        return redirect(URLROOT.'/encomiendas/recibo/'.$resultado['id']);
    }

    public function recibo(int $id)
    {
        $encomienda = $this->encomiendas->obtenerEncomiendaPorId($id);
        if (! $encomienda) {
            abort(404, 'Encomienda no encontrada');
        }

        return view('encomiendas.recibo', ['data' => ['encomienda' => $encomienda]]);
    }
}
