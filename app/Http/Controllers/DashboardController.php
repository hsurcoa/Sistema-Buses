<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel de control (Fase 2 de la migracion, Tarea 3; reescrito a Eloquent
 * al cerrar la sesion — ver informe de fin de sesion).
 *
 * Puerto de `legacy/app/controllers/Dashboard.php`. El helper legacy
 * `Sucursal` (basado en $_SESSION) ya se habia reemplazado por
 * `auth()->user()` en la Fase 2.
 */
class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /** Periodos del tablero: clave => [etiqueta, dias hacia atras incluyendo hoy]. */
    private const PERIODOS = [
        'hoy' => ['Hoy', 1],
        '7d' => ['7 días', 7],
        '30d' => ['30 días', 30],
        'mes' => ['Este mes', null],
        '12m' => ['12 meses', 365],
    ];

    private const ROLES_GLOBALES = ['Administrador', 'Supervisor'];

    public function index(Request $request): View
    {
        $clave = array_key_exists($request->query('periodo', ''), self::PERIODOS) ? $request->query('periodo') : '30d';
        [$desde, $hasta] = $this->rango($clave);

        // Periodo anterior del mismo largo, para comparar.
        $dias = (int) ((strtotime($hasta) - strtotime($desde)) / 86400) + 1;
        $antHasta = date('Y-m-d', strtotime($desde.' -1 day'));
        $antDesde = date('Y-m-d', strtotime($antHasta.' -'.($dias - 1).' days'));

        $usuario = $request->user()->loadMissing('rol');
        $rolNombre = $usuario->rol?->nombre ?? '';
        $esAdmin = $rolNombre === 'Administrador';
        $esGlobal = in_array($rolNombre, self::ROLES_GLOBALES, true);

        $sucursal = $esGlobal
            ? ((int) $request->query('sucursal', 0) ?: null)
            : ((int) $usuario->sucursal_id ?: 0);

        $ventas = $this->dashboard->resumenVentas($desde, $hasta, $sucursal);
        $ventasAnt = $this->dashboard->resumenVentas($antDesde, $antHasta, $sucursal);
        $ocupacion = $this->dashboard->ocupacion($desde, $hasta, $sucursal);

        $data = [
            'title' => 'Panel de control',
            'periodos' => self::PERIODOS,
            'periodo' => $clave,
            'desde' => $desde,
            'hasta' => $hasta,
            'sucursal_id' => $sucursal,
            'sucursal_nombre' => $sucursal ? Terminal::find($sucursal)?->nombre_sede : null,
            'sucursales' => $esGlobal ? Terminal::where('estado', 1)->orderBy('nombre_sede')->get() : collect(),
            'es_global' => $esGlobal,
            'ventas' => $ventas,
            'variacion' => $ventasAnt->ingresos > 0 ? (($ventas->ingresos - $ventasAnt->ingresos) / $ventasAnt->ingresos) * 100 : null,
            'ocupacion' => $ocupacion,
            'serie_diaria' => $this->completarDias($this->dashboard->ventasPorDia($desde, $hasta, $sucursal), $desde, $hasta, $clave),
            'por_grupo' => $this->dashboard->ventasPorGrupo($desde, $hasta, $sucursal),
            'rutas' => $this->dashboard->rutasMasVendidas($desde, $hasta, $sucursal),
            'viajes' => $this->dashboard->proximosViajes($sucursal),
            'cajas' => $this->dashboard->cajasAbiertas($sucursal),
            'encomiendas' => $this->dashboard->resumenEncomiendas($desde, $hasta, $sucursal),
            'pendientes' => $this->dashboard->pendientes($sucursal, $esAdmin),
            'ultima_venta' => $ventas->boletos ? null : $this->dashboard->ultimaVenta($sucursal),
            'sin_sucursal' => !$esGlobal && !$usuario->sucursal_id,
        ];

        return view('dashboard.index', ['data' => $data]);
    }

    private function rango(string $clave): array
    {
        $hoy = date('Y-m-d');
        if ($clave === 'mes') {
            return [date('Y-m-01'), $hoy];
        }
        $dias = self::PERIODOS[$clave][1];

        return [date('Y-m-d', strtotime($hoy.' -'.($dias - 1).' days')), $hoy];
    }

    /**
     * Serie continua para el grafico (dias sin ventas en 0). En 12 meses se
     * agrupa por mes para que el grafico siga siendo legible.
     */
    private function completarDias(iterable $filas, string $desde, string $hasta, string $clave): array
    {
        $porDia = [];
        foreach ($filas as $f) {
            $porDia[$f->dia] = $f;
        }

        if ($clave === '12m') {
            $serie = [];
            foreach ($filas as $f) {
                $mes = substr($f->dia, 0, 7);
                $serie[$mes] ??= ['etiqueta' => $mes, 'efectivo' => 0, 'qr' => 0, 'boletos' => 0];
                $serie[$mes]['efectivo'] += (float) $f->efectivo;
                $serie[$mes]['qr'] += (float) $f->qr;
                $serie[$mes]['boletos'] += (int) $f->boletos;
            }
            $meses = [];
            for ($t = strtotime(date('Y-m-01', strtotime($desde))); $t <= strtotime($hasta); $t = strtotime('+1 month', $t)) {
                $m = date('Y-m', $t);
                $meses[] = $serie[$m] ?? ['etiqueta' => $m, 'efectivo' => 0, 'qr' => 0, 'boletos' => 0];
            }
            $nombres = ['01' => 'ene', '02' => 'feb', '03' => 'mar', '04' => 'abr', '05' => 'may', '06' => 'jun', '07' => 'jul', '08' => 'ago', '09' => 'sep', '10' => 'oct', '11' => 'nov', '12' => 'dic'];
            foreach ($meses as &$m) {
                $m['etiqueta'] = $nombres[substr($m['etiqueta'], 5, 2)].' '.substr($m['etiqueta'], 2, 2);
            }

            return $meses;
        }

        $serie = [];
        for ($t = strtotime($desde); $t <= strtotime($hasta); $t += 86400) {
            $d = date('Y-m-d', $t);
            $serie[] = [
                'etiqueta' => date('d/m', $t),
                'efectivo' => (float) ($porDia[$d]->efectivo ?? 0),
                'qr' => (float) ($porDia[$d]->qr ?? 0),
                'boletos' => (int) ($porDia[$d]->boletos ?? 0),
            ];
        }

        return $serie;
    }
}
