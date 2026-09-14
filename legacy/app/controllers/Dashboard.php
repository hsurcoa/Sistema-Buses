<?php
class Dashboard extends Controller
{
    private $dashboardModel;

    /** Periodos del tablero: clave => [etiqueta, dias hacia atras incluyendo hoy] */
    private const PERIODOS = [
        'hoy' => ['Hoy', 1],
        '7d' => ['7 días', 7],
        '30d' => ['30 días', 30],
        'mes' => ['Este mes', null],
        '12m' => ['12 meses', 365],
    ];

    public function __construct()
    {
        $this->dashboardModel = $this->model('DashboardModel');
    }

    public function index()
    {
        $clave = array_key_exists($_GET['periodo'] ?? '', self::PERIODOS) ? $_GET['periodo'] : '30d';
        [$desde, $hasta] = $this->rango($clave);

        // Periodo anterior del mismo largo, para comparar
        $dias = (int) ((strtotime($hasta) - strtotime($desde)) / 86400) + 1;
        $antHasta = date('Y-m-d', strtotime($desde . ' -1 day'));
        $antDesde = date('Y-m-d', strtotime($antHasta . ' -' . ($dias - 1) . ' days'));

        $sucursal = Sucursal::filtro();          // null = todas (solo roles globales)
        $esGlobal = Sucursal::puedeVerTodas();
        $esAdmin = ($_SESSION['rol'] ?? '') === 'Administrador';

        $ventas = $this->dashboardModel->resumenVentas($desde, $hasta, $sucursal);
        $ventasAnt = $this->dashboardModel->resumenVentas($antDesde, $antHasta, $sucursal);
        $ocupacion = $this->dashboardModel->ocupacion($desde, $hasta, $sucursal);

        $data = [
            'title' => 'Panel de control',
            'periodos' => self::PERIODOS,
            'periodo' => $clave,
            'desde' => $desde,
            'hasta' => $hasta,
            'sucursal_id' => $sucursal,
            'sucursal_nombre' => $sucursal ? (Sucursal::obtener($sucursal)->nombre_sede ?? '') : null,
            'sucursales' => $esGlobal ? Sucursal::listar() : [],
            'es_global' => $esGlobal,
            'ventas' => $ventas,
            'variacion' => $ventasAnt->ingresos > 0 ? (($ventas->ingresos - $ventasAnt->ingresos) / $ventasAnt->ingresos) * 100 : null,
            'ocupacion' => $ocupacion,
            'serie_diaria' => $this->completarDias($this->dashboardModel->ventasPorDia($desde, $hasta, $sucursal), $desde, $hasta, $clave),
            'por_grupo' => $this->dashboardModel->ventasPorGrupo($desde, $hasta, $sucursal),
            'rutas' => $this->dashboardModel->rutasMasVendidas($desde, $hasta, $sucursal),
            'viajes' => $this->dashboardModel->proximosViajes($sucursal),
            'cajas' => $this->dashboardModel->cajasAbiertas($sucursal),
            'encomiendas' => $this->dashboardModel->resumenEncomiendas($desde, $hasta, $sucursal),
            'pendientes' => $this->dashboardModel->pendientes($sucursal, $esAdmin),
            'ultima_venta' => $ventas->boletos ? null : $this->dashboardModel->ultimaVenta($sucursal),
            'sin_sucursal' => !$esGlobal && !Sucursal::delUsuario(),
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('dashboard/index', $data);
        $this->view('layouts/footer', $data);
    }

    private function rango($clave)
    {
        $hoy = date('Y-m-d');
        if ($clave === 'mes') {
            return [date('Y-m-01'), $hoy];
        }
        $dias = self::PERIODOS[$clave][1];
        return [date('Y-m-d', strtotime($hoy . ' -' . ($dias - 1) . ' days')), $hoy];
    }

    /**
     * Serie continua para el grafico (dias sin ventas en 0). En 12 meses se
     * agrupa por mes para que el grafico siga siendo legible.
     */
    private function completarDias($filas, $desde, $hasta, $clave)
    {
        $porDia = [];
        foreach ($filas as $f) {
            $porDia[$f->dia] = $f;
        }

        $serie = [];
        if ($clave === '12m') {
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
                $m['etiqueta'] = $nombres[substr($m['etiqueta'], 5, 2)] . ' ' . substr($m['etiqueta'], 2, 2);
            }
            return $meses;
        }

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
