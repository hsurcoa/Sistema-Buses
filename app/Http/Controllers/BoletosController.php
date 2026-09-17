<?php

namespace App\Http\Controllers;

use App\Services\RutaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Puerto de `legacy/app/controllers/Boletos.php` y del script suelto
 * `legacy/public/print_ticket.php` (impresion termica publica por query
 * string, ya sin el bootstrap legacy). Las acciones `procesar_nuevo`/
 * `gestion_reserva` del controlador legacy duplican
 * `VentasController::procesarVenta()`/`gestionBoleto()` y no estan
 * enlazadas desde las vistas actuales: no se portan.
 */
class BoletosController extends Controller
{
    public function __construct(private RutaService $rutas) {}

    public function imprimirTicket(int $id)
    {
        $ticket = $this->rutas->obtenerDatosTicket($id);

        if (! $ticket) {
            abort(404, 'Ticket no encontrado');
        }

        return view('ventas.ticket_impresion', ['data' => ['ticket' => $ticket]]);
    }

    /** Puerto de `legacy/public/print_ticket.php` (query string ?id=). */
    public function printPublico(Request $request)
    {
        $id = $request->query('id');
        if (! $id) {
            abort(400, 'Error: Ticket ID Required.');
        }

        $ticket = DB::table('boletos as b')
            ->join('clientes as c', 'b.cliente_id', '=', 'c.id')
            ->join('viajes as v', 'b.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->leftJoin('rutas_paradas as rp', 'rp.id', '=', 'b.parada_id')
            ->leftJoin('terminales as suc', 'suc.id', '=', 'b.sucursal_id')
            ->leftJoin('vehiculos as veh', 'v.bus_id', '=', 'veh.id')
            ->leftJoin('buses as bs', function ($j) {
                $j->on('veh.placa', '=', 'bs.placa');
            })
            ->leftJoin('personal as u', 'v.chofer_id', '=', 'u.id')
            ->where('b.id', $id)->orWhere('b.codigo_boleto', $id)
            ->select([
                DB::raw('b.id as ticket_id'), 'b.codigo_boleto', 'b.numero_asiento', 'b.precio_final',
                DB::raw('b.fecha_reserva as fecha_emision'), 'b.metodo_pago', 'b.referencia_pago',
                DB::raw('suc.nombre_sede as sucursal_nombre'), DB::raw('suc.direccion as sucursal_direccion'), DB::raw('suc.telefono as sucursal_telefono'),
                'c.nombres', 'c.apellidos', 'c.numero_documento',
                'v.fecha_salida', 'v.hora_salida', 'r.origen',
                DB::raw('COALESCE(rp.nombre_parada, r.destino) AS destino'),
                DB::raw('veh.placa as bus_placa'), DB::raw('bs.numero_interno as bus_numero'),
                DB::raw('u.nombres as chofer_nombres'), DB::raw('u.apellidos as chofer_apellidos'),
            ])
            ->limit(1)
            ->first();

        if (! $ticket) {
            abort(404, 'Error: Ticket not found or invalid.');
        }

        $empresaCfg = DB::table('configuracion_sistema')->whereIn('clave', ['empresa_nombre', 'empresa_nit'])->pluck('valor', 'clave');

        return view('ventas.print_ticket_publico', ['data' => [
            'ticket' => $ticket,
            'empresa' => ['nombre' => $empresaCfg['empresa_nombre'] ?? SITENAME, 'nit' => $empresaCfg['empresa_nit'] ?? ''],
            'nombre_completo' => strtoupper($ticket->nombres.' '.$ticket->apellidos),
            'origen' => strtoupper($ticket->origen),
            'destino' => strtoupper($ticket->destino),
            'fecha' => date('d/m/Y', strtotime($ticket->fecha_salida)),
            'hora' => substr($ticket->hora_salida, 0, 5),
            'precio' => number_format($ticket->precio_final, 2),
            'nombre_chofer' => $ticket->chofer_nombres ? strtoupper($ticket->chofer_nombres.' '.$ticket->chofer_apellidos) : '---',
        ]]);
    }
}
