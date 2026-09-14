<?php
class Caja extends Controller
{
    private $cajaModel;
    private $sessionManager;

    public function __construct()
    {
        // Inicializar Gestor de Sesiones
        require_once '../app/core/SessionManager.php';
        $this->sessionManager = SessionManager::getInstance();
        $this->sessionManager->requireAuth(); // Redirige al login si no hay sesión

        $this->cajaModel = $this->model('CajaModel');
    }

    public function index()
    {

        // Verificar si hay caja abierta
        $userId = $this->sessionManager->getUserId();
        $cajaAbierta = $this->cajaModel->verificarCajaAbierta($userId);

        $data = [
            'titulo' => 'Gestión de Caja',
            'title' => 'Control de Caja',
            'caja_abierta' => $cajaAbierta
        ];

        if ($cajaAbierta) {
            // Si está abierta, mostrar panel de resumen
            $resumen = $this->cajaModel->obtenerResumenSesion($cajaAbierta->id);
            $data['resumen'] = $resumen;

            // Cargar vistas con layout completo
            $this->view('layouts/header', $data);
            $this->view('layouts/sidebar', $data);
            $this->view('caja/index', $data);
            $this->view('layouts/footer', $data);
        } else {
            // Si está cerrada, mostrar formulario de apertura
            $this->view('layouts/header', $data);
            $this->view('layouts/sidebar', $data);
            $this->view('caja/apertura', $data);
            $this->view('layouts/footer', $data);
        }
    }

    public function abrir()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // SEGURIDAD: CSRF
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                die('<script>alert("Error de Seguridad: Token CSRF inválido."); window.history.back();</script>');
            }

            $monto = trim($_POST['monto_inicial']);
            $userId = $this->sessionManager->getUserId();

            if ($this->cajaModel->abrirCaja($userId, $monto)) {
                // Actualizar sesión para saber que hay caja
                $caja = $this->cajaModel->verificarCajaAbierta($userId);
                $this->sessionManager->setUserData(['caja_sesion_id' => $caja->id]);

                header('Location: ' . URLROOT . '/caja');
                exit;
            } else {
                error_log("Error al abrir caja para usuario $userId");
                die('<script>alert("Error al abrir la caja. Intente nuevamente."); window.history.back();</script>');
            }
        }
    }

    public function cerrar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // SEGURIDAD: CSRF
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                die('<script>alert("Error de Seguridad: Token CSRF inválido."); window.history.back();</script>');
            }

            $montoReal = trim($_POST['monto_real']);
            $userId = $this->sessionManager->getUserId();
            $cajaAbierta = $this->cajaModel->verificarCajaAbierta($userId);

            if (!$cajaAbierta) {
                die('<script>alert("Error: No hay caja abierta para cerrar."); window.history.back();</script>');
            }

            $cajaId = $cajaAbierta->id;

            if ($this->cajaModel->cerrarCaja($cajaId, $montoReal)) {
                unset($_SESSION['caja_sesion_id']);
                header('Location: ' . URLROOT . '/caja/reporte/' . $cajaId);
                exit;
            } else {
                error_log("Error al cerrar caja $cajaId");
                die('<script>alert("Error interno al cerrar la caja. Intente nuevamente."); window.history.back();</script>');
            }
        }
    }

    // --- NUEVO: REGISTRO DE GASTOS ---
    public function registrar_gasto_ajax()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');

            // SEGURIDAD: CSRF
            $token = $_POST['csrf_token'] ?? '';
            if (!$this->sessionManager->verifyCsrfToken($token)) {
                echo json_encode(['status' => 'error', 'message' => 'Error de Seguridad: Token CSRF inválido.']);
                exit;
            }

            // 1. Validar Inputs
            $monto = floatval($_POST['monto'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($monto <= 0 || empty($descripcion) || empty($password)) {
                echo json_encode(['status' => 'error', 'message' => 'Datos incompletos o inválidos.']);
                exit;
            }

            // 2. Verificar Caja Abierta
            $userId = $this->sessionManager->getUserId();
            $cajaAbierta = $this->cajaModel->verificarCajaAbierta($userId);

            if (!$cajaAbierta) {
                echo json_encode(['status' => 'error', 'message' => 'No hay una caja abierta para registrar gastos.']);
                exit;
            }

            // 3. Validar Saldo Disponible (Opcional pero recomendado)
            $resumen = $this->cajaModel->obtenerResumenSesion($cajaAbierta->id);
            // Solo hay en el cajon el efectivo (los cobros QR no se pueden gastar desde caja)
            $saldoActual = ($resumen->monto_inicial + $resumen->total_efectivo) - $resumen->total_egresos;

            if ($monto > $saldoActual) {
                echo json_encode(['status' => 'error', 'message' => 'Fondos insuficientes en caja para este gasto.']);
                exit;
            }

            // 4. Validar Clave de Autorización (Asume que el password es del usuario actual)
            // Nota: validarCredencialesAdmin verificaba contra un admin genérico o el usuario actual?
            // Dejaremos la lógica, asumiendo que el modelo sabe qué hacer.
            if (!$this->cajaModel->validarCredencialesAdmin($password)) {
                echo json_encode(['status' => 'error', 'message' => 'Clave de Autorización Incorrecta.']);
                exit;
            }

            try {
                // 5. Registrar Egreso
                if ($this->cajaModel->registrarMovimiento($cajaAbierta->id, 'EGRESO', 'GASTO', null, $monto, $descripcion)) {
                    echo json_encode(['status' => 'success', 'message' => 'Gasto registrado correctamente.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No se pudo registrar el gasto.']);
                }
            } catch (Exception $e) {
                error_log("Error registrar_gasto: " . $e->getMessage());
                echo json_encode(['status' => 'error', 'message' => 'Error interno del sistema.']);
            }
            exit;
        }
    }

    // --- NUEVO: REPORTE DE INGRESOS (AJAX) ---
    public function obtener_ingresos_ajax()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            header('Content-Type: application/json');

            // 1. Obtener Filtros
            $tipo = $_POST['tipo'] ?? 'dia'; // dia, semana, mes, anio, rango
            $inicio = $_POST['fecha_inicio'] ?? '';
            $fin = $_POST['fecha_fin'] ?? '';

            // 2. Calcular Rango de Fechas
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');

            if ($tipo === 'dia') {
                $fechaInicio = date('Y-m-d');
                $fechaFin = date('Y-m-d');
            } elseif ($tipo === 'semana') {
                // Últimos 7 días
                $fechaInicio = date('Y-m-d', strtotime('-6 days'));
                $fechaFin = date('Y-m-d');
            } elseif ($tipo === 'mes') {
                // Mes actual
                $fechaInicio = date('Y-m-01');
                $fechaFin = date('Y-m-t'); // t = último día del mes
            } elseif ($tipo === 'anio') {
                // Año actual
                $fechaInicio = date('Y-01-01');
                $fechaFin = date('Y-12-31');
            } elseif ($tipo === 'rango') {
                if (empty($inicio) || empty($fin)) {
                    echo json_encode(['status' => 'error', 'message' => 'Fechas inválidas para rango personalizado.']);
                    exit;
                }
                $fechaInicio = $inicio;
                $fechaFin = $fin;
            }

            // 3. Consultar Modelo
            // Necesitamos agregar este método en CajaModel
            $ingresos = $this->cajaModel->obtenerReporteIngresos($fechaInicio, $fechaFin);

            echo json_encode([
                'status' => 'success',
                'data' => $ingresos,
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ]
            ]);
            exit;
        }
    }

    public function reporte($id)
    {
        // Obtener datos de la sesión cerrada
        $sesion = $this->cajaModel->obtenerSesionPorId($id);

        if (!$sesion) {
            header('Location: ' . URLROOT . '/caja');
            exit;
        }

        $data = [
            'titulo' => 'Reporte de Cierre',
            'title' => 'Reporte de Cierre de Caja',
            'sesion' => $sesion
        ];

        $this->view('layouts/header', $data);
        $this->view('layouts/sidebar', $data);
        $this->view('caja/reporte', $data);
        $this->view('layouts/footer', $data);
    }

    // --- NUEVO: REPORTE Z PARA IMPRESIÓN ---
    public function reporte_z($id)
    {
        // Obtener datos de la sesión cerrada
        $sesion = $this->cajaModel->obtenerSesionPorId($id);

        if (!$sesion) {
            die('Sesión de caja no encontrada');
        }

        // Obtener movimientos detallados de la sesión
        $movimientos = $this->cajaModel->obtenerMovimientosPorSesion($id);

        $data = [
            'sesion' => $sesion,
            'movimientos' => $movimientos
        ];

        // Cargar vista sin layout (solo para impresión)
        $this->view('caja/reporte_z', $data);
    }
    // --- NUEVO: ENDPOINT AJAX PARA REPORTES DE CIERRE ---
    public function obtener_reportes_cierre_ajax()
    {
        // Verificar si es petición AJAX
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
            return;
        }

        header('Content-Type: application/json');

        try {
            $tipo = $_POST['tipo'] ?? 'dia';
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
            $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');

            // Calcular fechas según tipo
            switch ($tipo) {
                case 'dia':
                    $fechaInicio = date('Y-m-d');
                    $fechaFin = date('Y-m-d');
                    break;
                case 'semana':
                    $fechaInicio = date('Y-m-d', strtotime('monday this week'));
                    $fechaFin = date('Y-m-d', strtotime('sunday this week'));
                    break;
                case 'mes':
                    $fechaInicio = date('Y-m-01');
                    $fechaFin = date('Y-m-t');
                    break;
                case 'anio':
                    $fechaInicio = date('Y-01-01');
                    $fechaFin = date('Y-12-31');
                    break;
                case 'rango':
                    // Usar las fechas enviadas por POST
                    break;
            }

            $sesiones = $this->cajaModel->obtenerSesionesCerradas($fechaInicio, $fechaFin);
            $estadisticas = $this->cajaModel->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin);

            echo json_encode([
                'status' => 'success',
                'data' => $sesiones,
                'stats' => $estadisticas,
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener reportes: ' . $e->getMessage()
            ]);
        }
    }

    // --- NUEVO: EXPORTAR A EXCEL ---
    public function exportar_excel()
    {
        // Verificar dependencias
        if (!file_exists(APPROOT . '/../vendor/autoload.php')) {
            die('Error: Librería PhpSpreadsheet no instalada. Ejecute: composer require phpoffice/phpspreadsheet');
        }
        require_once APPROOT . '/../vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');

        $sesiones = $this->cajaModel->obtenerSesionesCerradas($fechaInicio, $fechaFin);
        $stats = $this->cajaModel->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin);

        // Título
        $sheet->setCellValue('A1', 'REPORTE DE CIERRES DE CAJA');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Período: ' . $fechaInicio . ' al ' . $fechaFin);
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Encabezados
        $headers = ['Turno', 'Cajero', 'Apertura', 'Cierre', 'Inicial', 'Ingresos', 'Egresos', 'Sistema', 'Real', 'Diferencia'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $sheet->getStyle($col . '4')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4');
            $sheet->getStyle($col . '4')->getFont()->getColor()->setARGB('FFFFFFFF');
            $col++;
        }

        // Datos
        $row = 5;
        foreach ($sesiones as $sesion) {
            $sheet->setCellValue('A' . $row, '#' . $sesion->id);
            $sheet->setCellValue('B' . $row, $sesion->cajero_nombre);
            $sheet->setCellValue('C' . $row, date('d/m/Y H:i', strtotime($sesion->fecha_apertura)));
            $sheet->setCellValue('D' . $row, date('d/m/Y H:i', strtotime($sesion->fecha_cierre)));
            $sheet->setCellValue('E' . $row, number_format($sesion->monto_inicial, 2));
            $sheet->setCellValue('F' . $row, number_format($sesion->total_ingresos, 2));
            $sheet->setCellValue('G' . $row, number_format($sesion->total_egresos, 2));
            $sheet->setCellValue('H' . $row, number_format($sesion->monto_final_sistema, 2));
            $sheet->setCellValue('I' . $row, number_format($sesion->monto_final_real, 2));
            $sheet->setCellValue('J' . $row, number_format($sesion->diferencia, 2));
            $row++;
        }

        // Totales
        $row++;
        $sheet->setCellValue('A' . $row, 'TOTALES');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('B' . $row, $stats->total_sesiones . ' sesiones');
        $sheet->setCellValue('E' . $row, number_format($stats->suma_inicial, 2));
        $sheet->setCellValue('H' . $row, number_format($stats->suma_sistema, 2));
        $sheet->setCellValue('I' . $row, number_format($stats->suma_real, 2));
        $sheet->setCellValue('J' . $row, number_format($stats->suma_diferencias, 2));

        // Ajustar anchos
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Descargar
        $filename = 'Reporte_Cierres_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // --- NUEVO: EXPORTAR A WORD ---
    public function exportar_word()
    {
        if (!file_exists(APPROOT . '/../vendor/autoload.php')) {
            die('Error: Librería PHPWord no instalada. Ejecute: composer require phpoffice/phpword');
        }
        require_once APPROOT . '/../vendor/autoload.php';

        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');

        $sesiones = $this->cajaModel->obtenerSesionesCerradas($fechaInicio, $fechaFin);
        $stats = $this->cajaModel->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();

        // Título
        $section->addText(
            'REPORTE DE CIERRES DE CAJA',
            ['bold' => true, 'size' => 18, 'color' => '1F4E78'],
            ['alignment' => 'center']
        );

        $section->addText(
            'Período: ' . date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin)),
            ['size' => 12],
            ['alignment' => 'center', 'spaceAfter' => 300]
        );

        // Tabla
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80
        ]);

        // Encabezado
        $table->addRow(500);
        $table->addCell(1000)->addText('Turno', ['bold' => true]);
        $table->addCell(3000)->addText('Cajero', ['bold' => true]);
        $table->addCell(2000)->addText('Apertura', ['bold' => true]);
        $table->addCell(2000)->addText('Cierre', ['bold' => true]);
        $table->addCell(1500)->addText('Sistema', ['bold' => true]);
        $table->addCell(1500)->addText('Real', ['bold' => true]);
        $table->addCell(1500)->addText('Diferencia', ['bold' => true]);

        // Datos
        foreach ($sesiones as $sesion) {
            $table->addRow();
            $table->addCell(1000)->addText('#' . $sesion->id);
            $table->addCell(3000)->addText($sesion->cajero_nombre);
            $table->addCell(2000)->addText(date('d/m/Y H:i', strtotime($sesion->fecha_apertura)));
            $table->addCell(2000)->addText(date('d/m/Y H:i', strtotime($sesion->fecha_cierre)));
            $table->addCell(1500)->addText('Bs. ' . number_format($sesion->monto_final_sistema, 2));
            $table->addCell(1500)->addText('Bs. ' . number_format($sesion->monto_final_real, 2));
            $table->addCell(1500)->addText('Bs. ' . number_format($sesion->diferencia, 2));
        }

        // Resumen
        $section->addTextBreak(2);
        $section->addText('RESUMEN CONSOLIDADO', ['bold' => true, 'size' => 14]);
        $section->addText('Total de sesiones: ' . $stats->total_sesiones);
        $section->addText('Total sistema: Bs. ' . number_format($stats->suma_sistema, 2));
        $section->addText('Total real: Bs. ' . number_format($stats->suma_real, 2));
        $section->addText('Diferencia total: Bs. ' . number_format($stats->suma_diferencias, 2));

        // Descargar
        $filename = 'Reporte_Cierres_' . date('Y-m-d_His') . '.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save('php://output');
        exit;
    }

    // --- NUEVO: EXPORTAR A PDF ---
    public function exportar_pdf($tamano = 'carta')
    {
        require_once APPROOT . '/../vendor/autoload.php';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);

        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');

        $sesiones = $this->cajaModel->obtenerSesionesCerradas($fechaInicio, $fechaFin);
        $stats = $this->cajaModel->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin);

        $pageSize = ($tamano === 'a4') ? 'A4' : 'letter';

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
        $html .= '<h2>Período: ' . date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin)) . '</h2>';

        $html .= '<table><thead><tr>
            <th>Turno</th><th>Cajero</th><th>Apertura</th><th>Cierre</th>
            <th class="text-right">Inicial</th><th class="text-right">Ingresos</th>
            <th class="text-right">Egresos</th><th class="text-right">Sistema</th>
            <th class="text-right">Real</th><th class="text-right">Diferencia</th>
        </tr></thead><tbody>';

        foreach ($sesiones as $sesion) {
            $html .= '<tr>
                <td>#' . $sesion->id . '</td>
                <td>' . $sesion->cajero_nombre . '</td>
                <td>' . date('d/m/Y H:i', strtotime($sesion->fecha_apertura)) . '</td>
                <td>' . date('d/m/Y H:i', strtotime($sesion->fecha_cierre)) . '</td>
                <td class="text-right">Bs. ' . number_format($sesion->monto_inicial, 2) . '</td>
                <td class="text-right">Bs. ' . number_format($sesion->total_ingresos, 2) . '</td>
                <td class="text-right">Bs. ' . number_format($sesion->total_egresos, 2) . '</td>
                <td class="text-right">Bs. ' . number_format($sesion->monto_final_sistema, 2) . '</td>
                <td class="text-right">Bs. ' . number_format($sesion->monto_final_real, 2) . '</td>
                <td class="text-right">Bs. ' . number_format($sesion->diferencia, 2) . '</td>
            </tr>';
        }

        $html .= '<tr class="totals">
            <td colspan="4">TOTALES (' . $stats->total_sesiones . ' sesiones)</td>
            <td class="text-right">Bs. ' . number_format($stats->suma_inicial, 2) . '</td>
            <td colspan="2"></td>
            <td class="text-right">Bs. ' . number_format($stats->suma_sistema, 2) . '</td>
            <td class="text-right">Bs. ' . number_format($stats->suma_real, 2) . '</td>
            <td class="text-right">Bs. ' . number_format($stats->suma_diferencias, 2) . '</td>
        </tr></tbody></table></body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper($pageSize, 'portrait');
        $dompdf->render();

        $filename = 'Reporte_Cierres_' . strtoupper($tamano) . '_' . date('Y-m-d_His') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}
