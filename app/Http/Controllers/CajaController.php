<?php

namespace App\Http\Controllers;

use App\Models\Terminal;
use App\Services\CajaService;
use Illuminate\Http\Request;

/**
 * Control de caja (apertura/cierre, movimientos, reportes de cierre y sus
 * exportaciones). Puerto de `legacy/app/controllers/Caja.php` (Fase 4;
 * reescrito a Eloquent al cerrar la sesion — ver informe de fin de sesion).
 */
class CajaController extends Controller
{
    private const ROLES_GLOBALES = ['Administrador', 'Supervisor'];

    public function __construct(private CajaService $caja) {}

    private function puedeVerTodas(Request $request): bool
    {
        return in_array($request->user()->rol?->nombre, self::ROLES_GLOBALES, true);
    }

    /** Sucursal por la que se filtran reportes: elegida (roles globales) o la propia. */
    private function filtroSucursal(Request $request): ?int
    {
        if ($this->puedeVerTodas($request)) {
            $elegida = (int) $request->input('sucursal', 0);

            return $elegida > 0 ? $elegida : null;
        }

        return (int) ($request->user()->sucursal_id ?? 0);
    }

    public function index(Request $request)
    {
        $cajaAbierta = $this->caja->verificarCajaAbierta($request->user()->id);

        $data = [
            'titulo' => 'Gestión de Caja',
            'title' => 'Control de Caja',
            'caja_abierta' => $cajaAbierta,
            'sucursal_usuario' => $request->user()->sucursal_id ? Terminal::find($request->user()->sucursal_id) : null,
            'puede_elegir_sucursal' => $this->puedeVerTodas($request),
            'sucursales' => Terminal::orderBy('nombre_sede')->get(),
        ];

        if ($cajaAbierta) {
            $data['resumen'] = $this->caja->obtenerResumenSesion($cajaAbierta->id);

            return view('caja.index', ['data' => $data]);
        }

        return view('caja.apertura', ['data' => $data]);
    }

    public function abrir(Request $request)
    {
        $monto = trim($request->input('monto_inicial', ''));
        $userId = $request->user()->id;

        $sucursalId = (int) ($request->user()->sucursal_id ?? 0);
        if ($this->puedeVerTodas($request) && $request->filled('sucursal_id')) {
            $sucursalId = (int) $request->input('sucursal_id');
        }
        $sucursal = $sucursalId ? Terminal::find($sucursalId) : null;
        if (! $sucursal || ! $sucursal->estado) {
            $msg = $this->puedeVerTodas($request)
                ? 'Seleccione la sucursal donde abrirá la caja.'
                : 'Su usuario no tiene una sucursal asignada. Pida al administrador que se la asigne en Usuarios del sistema.';

            return response('<script>alert('.json_encode($msg).'); window.history.back();</script>');
        }

        if ($this->caja->verificarCajaAbierta($userId)) {
            return redirect(URLROOT.'/caja');
        }

        if ($this->caja->abrirCaja($userId, $monto, $sucursal->id)) {
            return redirect(URLROOT.'/caja');
        }

        return response('<script>alert("Error al abrir la caja. Intente nuevamente."); window.history.back();</script>');
    }

    public function cerrar(Request $request)
    {
        $montoReal = trim($request->input('monto_real', ''));
        $cajaAbierta = $this->caja->verificarCajaAbierta($request->user()->id);

        if (! $cajaAbierta) {
            return response('<script>alert("Error: No hay caja abierta para cerrar."); window.history.back();</script>');
        }

        if ($this->caja->cerrarCaja($cajaAbierta->id, $montoReal)) {
            return redirect(URLROOT.'/caja/reporte/'.$cajaAbierta->id);
        }

        return response('<script>alert("Error interno al cerrar la caja. Intente nuevamente."); window.history.back();</script>');
    }

    public function registrarGastoAjax(Request $request)
    {
        $monto = (float) $request->input('monto', 0);
        $descripcion = trim($request->input('descripcion', ''));
        $password = $request->input('password', '');

        if ($monto <= 0 || $descripcion === '' || $password === '') {
            return response()->json(['status' => 'error', 'message' => 'Datos incompletos o inválidos.']);
        }

        $cajaAbierta = $this->caja->verificarCajaAbierta($request->user()->id);
        if (! $cajaAbierta) {
            return response()->json(['status' => 'error', 'message' => 'No hay una caja abierta para registrar gastos.']);
        }

        $resumen = $this->caja->obtenerResumenSesion($cajaAbierta->id);
        $saldoActual = $resumen->monto_inicial + $resumen->total_efectivo - $resumen->total_egresos;

        if ($monto > $saldoActual) {
            return response()->json(['status' => 'error', 'message' => 'Fondos insuficientes en caja para este gasto.']);
        }

        if (! $this->caja->validarCredencialesAdmin($password)) {
            return response()->json(['status' => 'error', 'message' => 'Clave de Autorización Incorrecta.']);
        }

        try {
            return $this->caja->registrarMovimiento($cajaAbierta->id, 'EGRESO', 'GASTO', null, $monto, $descripcion)
                ? response()->json(['status' => 'success', 'message' => 'Gasto registrado correctamente.'])
                : response()->json(['status' => 'error', 'message' => 'No se pudo registrar el gasto.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error interno del sistema.']);
        }
    }

    /** Movimientos de la sesión abierta del usuario, con la vía de pago resuelta (Efectivo/QR fijo/Libélula), para la tabla en vivo del dashboard. */
    public function obtenerMovimientosSesionAjax(Request $request)
    {
        $cajaAbierta = $this->caja->verificarCajaAbierta($request->user()->id);
        if (! $cajaAbierta) {
            return response()->json(['status' => 'error', 'message' => 'No hay una caja abierta.']);
        }

        $tipo = in_array($request->input('tipo'), ['INGRESO', 'EGRESO'], true) ? $request->input('tipo') : null;
        $via = in_array($request->input('via'), ['EFECTIVO', 'QR_FIJO', 'LIBELULA'], true) ? $request->input('via') : null;

        return response()->json([
            'status' => 'success',
            'data' => $this->caja->obtenerMovimientosSesion($cajaAbierta->id, $tipo, $via),
        ]);
    }

    public function obtenerIngresosAjax(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->rangoFechas($request);
        if ($request->input('tipo', 'dia') === 'rango' && (! $request->filled('fecha_inicio') || ! $request->filled('fecha_fin'))) {
            return response()->json(['status' => 'error', 'message' => 'Fechas inválidas para rango personalizado.']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->caja->obtenerReporteIngresos($fechaInicio, $fechaFin),
            'periodo' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
        ]);
    }

    public function reporte(int $id)
    {
        $sesion = $this->caja->obtenerSesionPorId($id);

        if (! $sesion) {
            return redirect(URLROOT.'/caja');
        }

        return view('caja.reporte', ['data' => [
            'titulo' => 'Reporte de Cierre',
            'title' => 'Reporte de Cierre de Caja',
            'sesion' => $sesion,
        ]]);
    }

    public function reporteZ(int $id)
    {
        $sesion = $this->caja->obtenerSesionPorId($id);

        if (! $sesion) {
            abort(404, 'Sesión de caja no encontrada');
        }

        return view('caja.reporte_z', ['data' => [
            'sesion' => $sesion,
            'movimientos' => $this->caja->obtenerMovimientosPorSesion($id),
        ]]);
    }

    public function obtenerReportesCierreAjax(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->rangoFechas($request, incluirRango: true);

        try {
            $filtro = $this->filtroSucursal($request);

            return response()->json([
                'status' => 'success',
                'data' => $this->caja->obtenerSesionesCerradas($fechaInicio, $fechaFin, $filtro),
                'stats' => $this->caja->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin, $filtro),
                'periodo' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error al obtener reportes: '.$e->getMessage()]);
        }
    }

    public function exportarExcel(Request $request)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $fechaInicio = $request->input('fecha_inicio', date('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', date('Y-m-d'));
        $filtro = $this->filtroSucursal($request);

        $sesiones = $this->caja->obtenerSesionesCerradas($fechaInicio, $fechaFin, $filtro);
        $stats = $this->caja->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin, $filtro);

        $sheet->setCellValue('A1', 'REPORTE DE CIERRES DE CAJA');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Período: '.$fechaInicio.' al '.$fechaFin);
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['Turno', 'Cajero', 'Apertura', 'Cierre', 'Inicial', 'Efectivo', 'QR Fijo', 'Libélula', 'Egresos', 'Sistema', 'Real', 'Diferencia'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'4', $header);
            $sheet->getStyle($col.'4')->getFont()->setBold(true);
            $sheet->getStyle($col.'4')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4');
            $sheet->getStyle($col.'4')->getFont()->getColor()->setARGB('FFFFFFFF');
            $col++;
        }

        $row = 5;
        foreach ($sesiones as $sesion) {
            $sheet->setCellValue('A'.$row, '#'.$sesion->id);
            $sheet->setCellValue('B'.$row, $sesion->cajero_nombre);
            $sheet->setCellValue('C'.$row, date('d/m/Y H:i', strtotime($sesion->fecha_apertura)));
            $sheet->setCellValue('D'.$row, date('d/m/Y H:i', strtotime($sesion->fecha_cierre)));
            $sheet->setCellValue('E'.$row, number_format($sesion->monto_inicial, 2));
            $sheet->setCellValue('F'.$row, number_format($sesion->total_efectivo, 2));
            $sheet->setCellValue('G'.$row, number_format($sesion->total_qr_fijo, 2));
            $sheet->setCellValue('H'.$row, number_format($sesion->total_qr_libelula, 2));
            $sheet->setCellValue('I'.$row, number_format($sesion->total_egresos, 2));
            $sheet->setCellValue('J'.$row, number_format($sesion->monto_final_sistema, 2));
            $sheet->setCellValue('K'.$row, number_format($sesion->monto_final_real, 2));
            $sheet->setCellValue('L'.$row, number_format($sesion->diferencia, 2));
            $row++;
        }

        $row++;
        $sheet->setCellValue('A'.$row, 'TOTALES');
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $sheet->setCellValue('B'.$row, $stats->total_sesiones.' sesiones');
        $sheet->setCellValue('E'.$row, number_format($stats->suma_inicial, 2));
        $sheet->setCellValue('F'.$row, number_format($stats->suma_efectivo, 2));
        $sheet->setCellValue('G'.$row, number_format($stats->suma_qr_fijo, 2));
        $sheet->setCellValue('H'.$row, number_format($stats->suma_qr_libelula, 2));
        $sheet->setCellValue('J'.$row, number_format($stats->suma_sistema, 2));
        $sheet->setCellValue('K'.$row, number_format($stats->suma_real, 2));
        $sheet->setCellValue('L'.$row, number_format($stats->suma_diferencias, 2));

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Reporte_Cierres_'.date('Y-m-d_His').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    public function exportarWord(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', date('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', date('Y-m-d'));
        $filtro = $this->filtroSucursal($request);

        $sesiones = $this->caja->obtenerSesionesCerradas($fechaInicio, $fechaFin, $filtro);
        $stats = $this->caja->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin, $filtro);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        $section->addText('REPORTE DE CIERRES DE CAJA', ['bold' => true, 'size' => 18, 'color' => '1F4E78'], ['alignment' => 'center']);
        $section->addText(
            'Período: '.date('d/m/Y', strtotime($fechaInicio)).' al '.date('d/m/Y', strtotime($fechaFin)),
            ['size' => 12],
            ['alignment' => 'center', 'spaceAfter' => 300]
        );

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);

        $table->addRow(500);
        $table->addCell(900)->addText('Turno', ['bold' => true]);
        $table->addCell(2200)->addText('Cajero', ['bold' => true]);
        $table->addCell(1600)->addText('Apertura', ['bold' => true]);
        $table->addCell(1600)->addText('Cierre', ['bold' => true]);
        $table->addCell(1300)->addText('Efectivo', ['bold' => true]);
        $table->addCell(1300)->addText('QR Fijo', ['bold' => true]);
        $table->addCell(1300)->addText('Libélula', ['bold' => true]);
        $table->addCell(1300)->addText('Sistema', ['bold' => true]);
        $table->addCell(1300)->addText('Real', ['bold' => true]);
        $table->addCell(1300)->addText('Diferencia', ['bold' => true]);

        foreach ($sesiones as $sesion) {
            $table->addRow();
            $table->addCell(900)->addText('#'.$sesion->id);
            $table->addCell(2200)->addText($sesion->cajero_nombre);
            $table->addCell(1600)->addText(date('d/m/Y H:i', strtotime($sesion->fecha_apertura)));
            $table->addCell(1600)->addText(date('d/m/Y H:i', strtotime($sesion->fecha_cierre)));
            $table->addCell(1300)->addText('Bs. '.number_format($sesion->total_efectivo, 2));
            $table->addCell(1300)->addText('Bs. '.number_format($sesion->total_qr_fijo, 2));
            $table->addCell(1300)->addText('Bs. '.number_format($sesion->total_qr_libelula, 2));
            $table->addCell(1300)->addText('Bs. '.number_format($sesion->monto_final_sistema, 2));
            $table->addCell(1300)->addText('Bs. '.number_format($sesion->monto_final_real, 2));
            $table->addCell(1300)->addText('Bs. '.number_format($sesion->diferencia, 2));
        }

        $section->addTextBreak(2);
        $section->addText('RESUMEN CONSOLIDADO', ['bold' => true, 'size' => 14]);
        $section->addText('Total de sesiones: '.$stats->total_sesiones);
        $section->addText('Total efectivo: Bs. '.number_format($stats->suma_efectivo, 2));
        $section->addText('Total QR fijo: Bs. '.number_format($stats->suma_qr_fijo, 2));
        $section->addText('Total Libélula: Bs. '.number_format($stats->suma_qr_libelula, 2));
        $section->addText('Total sistema: Bs. '.number_format($stats->suma_sistema, 2));
        $section->addText('Total real: Bs. '.number_format($stats->suma_real, 2));
        $section->addText('Diferencia total: Bs. '.number_format($stats->suma_diferencias, 2));

        $filename = 'Reporte_Cierres_'.date('Y-m-d_His').'.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save('php://output');
        exit;
    }

    public function exportarPdf(Request $request, string $tamano = 'carta')
    {
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);

        $fechaInicio = $request->input('fecha_inicio', date('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', date('Y-m-d'));
        $filtro = $this->filtroSucursal($request);

        $sesiones = $this->caja->obtenerSesionesCerradas($fechaInicio, $fechaFin, $filtro);
        $stats = $this->caja->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin, $filtro);

        $pageSize = $tamano === 'a4' ? 'A4' : 'letter';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body { font-family: Arial, sans-serif; font-size: 10pt; }
            h1 { text-align: center; color: #1F4E78; margin-bottom: 5px; }
            h2 { text-align: center; font-size: 12pt; color: #666; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #4472C4; color: white; padding: 8px; text-align: left; }
            td { border: 1px solid #ddd; padding: 6px; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .totals { background-color: #E7E6E6; font-weight: bold; }
            .text-right { text-align: right; }
        </style></head><body>';

        $html .= '<h1>REPORTE DE CIERRES DE CAJA</h1>';
        $html .= '<h2>Período: '.date('d/m/Y', strtotime($fechaInicio)).' al '.date('d/m/Y', strtotime($fechaFin)).'</h2>';

        $html .= '<table><thead><tr>
            <th>Turno</th><th>Cajero</th><th>Apertura</th><th>Cierre</th>
            <th class="text-right">Inicial</th><th class="text-right">Efectivo</th>
            <th class="text-right">QR Fijo</th><th class="text-right">Libélula</th>
            <th class="text-right">Egresos</th><th class="text-right">Sistema</th>
            <th class="text-right">Real</th><th class="text-right">Diferencia</th>
        </tr></thead><tbody>';

        foreach ($sesiones as $sesion) {
            $html .= '<tr>
                <td>#'.$sesion->id.'</td>
                <td>'.$sesion->cajero_nombre.'</td>
                <td>'.date('d/m/Y H:i', strtotime($sesion->fecha_apertura)).'</td>
                <td>'.date('d/m/Y H:i', strtotime($sesion->fecha_cierre)).'</td>
                <td class="text-right">Bs. '.number_format($sesion->monto_inicial, 2).'</td>
                <td class="text-right">Bs. '.number_format($sesion->total_efectivo, 2).'</td>
                <td class="text-right">Bs. '.number_format($sesion->total_qr_fijo, 2).'</td>
                <td class="text-right">Bs. '.number_format($sesion->total_qr_libelula, 2).'</td>
                <td class="text-right">Bs. '.number_format($sesion->total_egresos, 2).'</td>
                <td class="text-right">Bs. '.number_format($sesion->monto_final_sistema, 2).'</td>
                <td class="text-right">Bs. '.number_format($sesion->monto_final_real, 2).'</td>
                <td class="text-right">Bs. '.number_format($sesion->diferencia, 2).'</td>
            </tr>';
        }

        $html .= '<tr class="totals">
            <td colspan="4">TOTALES ('.$stats->total_sesiones.' sesiones)</td>
            <td class="text-right">Bs. '.number_format($stats->suma_inicial, 2).'</td>
            <td class="text-right">Bs. '.number_format($stats->suma_efectivo, 2).'</td>
            <td class="text-right">Bs. '.number_format($stats->suma_qr_fijo, 2).'</td>
            <td class="text-right">Bs. '.number_format($stats->suma_qr_libelula, 2).'</td>
            <td></td>
            <td class="text-right">Bs. '.number_format($stats->suma_sistema, 2).'</td>
            <td class="text-right">Bs. '.number_format($stats->suma_real, 2).'</td>
            <td class="text-right">Bs. '.number_format($stats->suma_diferencias, 2).'</td>
        </tr></tbody></table></body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper($pageSize, 'portrait');
        $dompdf->render();

        $filename = 'Reporte_Cierres_'.strtoupper($tamano).'_'.date('Y-m-d_His').'.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /** @return array{0: string, 1: string} */
    private function rangoFechas(Request $request, bool $incluirRango = false): array
    {
        $tipo = $request->input('tipo', 'dia');
        $inicio = $request->input('fecha_inicio', '');
        $fin = $request->input('fecha_fin', '');

        return match ($tipo) {
            'semana' => $incluirRango
                ? [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))]
                : [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
            'mes' => [date('Y-m-01'), date('Y-m-t')],
            'anio' => [date('Y-01-01'), date('Y-12-31')],
            'rango' => [$inicio ?: date('Y-m-d'), $fin ?: date('Y-m-d')],
            default => [date('Y-m-d'), date('Y-m-d')],
        };
    }
}
