# 🚀 GUÍA RÁPIDA DE IMPLEMENTACIÓN
## Sistema de Reportes de Cierre de Caja

---

## ⚡ INICIO RÁPIDO (15 minutos)

### Paso 1: Instalar Dependencias (5 min)
```bash
cd c:\xampp\htdocs\venta-pasajes
composer require phpoffice/phpspreadsheet:^1.29 phpoffice/phpword:^1.1
```

**Verificar instalación:**
```bash
composer show | findstr phpoffice
```

Deberías ver:
```
phpoffice/phpspreadsheet  1.29.x
phpoffice/phpword         1.1.x
```

---

### Paso 2: Actualizar Modelo (10 min)

**Archivo:** `c:\xampp\htdocs\venta-pasajes\app\Models\CajaModel.php`

**Agregar al final de la clase (antes del último `}`):**

```php
/**
 * Obtener sesiones cerradas en un rango de fechas
 */
public function obtenerSesionesCerradas($fechaInicio, $fechaFin)
{
    $inicioFull = $fechaInicio . ' 00:00:00';
    $finFull = $fechaFin . ' 23:59:59';

    $sql = "SELECT 
                cs.id,
                cs.usuario_id,
                CONCAT(u.nombres, ' ', u.apellidos) as cajero_nombre,
                cs.fecha_apertura,
                cs.fecha_cierre,
                cs.monto_inicial,
                cs.monto_final_sistema,
                cs.monto_final_real,
                cs.diferencia,
                (SELECT SUM(monto) FROM movimientos_caja 
                 WHERE sesion_id = cs.id AND tipo_movimiento = 'INGRESO') as total_ingresos,
                (SELECT SUM(monto) FROM movimientos_caja 
                 WHERE sesion_id = cs.id AND tipo_movimiento = 'EGRESO') as total_egresos
            FROM cajas_sesiones cs
            INNER JOIN usuarios u ON cs.usuario_id = u.id
            WHERE cs.estado = 'CERRADA'
            AND cs.fecha_cierre BETWEEN :inicio AND :fin
            ORDER BY cs.fecha_cierre DESC";

    $this->db->query($sql);
    $this->db->bind(':inicio', $inicioFull);
    $this->db->bind(':fin', $finFull);

    return $this->db->resultSet();
}

/**
 * Obtener estadísticas consolidadas de un período
 */
public function obtenerEstadisticasPeriodo($fechaInicio, $fechaFin)
{
    $inicioFull = $fechaInicio . ' 00:00:00';
    $finFull = $fechaFin . ' 23:59:59';

    $sql = "SELECT 
                COUNT(*) as total_sesiones,
                SUM(monto_inicial) as suma_inicial,
                SUM(monto_final_sistema) as suma_sistema,
                SUM(monto_final_real) as suma_real,
                SUM(diferencia) as suma_diferencias,
                AVG(monto_final_sistema) as promedio_sistema
            FROM cajas_sesiones
            WHERE estado = 'CERRADA'
            AND fecha_cierre BETWEEN :inicio AND :fin";

    $this->db->query($sql);
    $this->db->bind(':inicio', $inicioFull);
    $this->db->bind(':fin', $finFull);

    return $this->db->single();
}
```

---

## 📝 IMPLEMENTACIÓN COMPLETA

### PARTE A: CONTROLADOR (30-40 min)

**Archivo:** `c:\xampp\htdocs\venta-pasajes\app\Controllers\Caja.php`

**Agregar estos métodos al final de la clase:**

```php
/**
 * Endpoint AJAX para obtener reportes de cierre
 */
public function obtener_reportes_cierre_ajax()
{
    header('Content-Type: application/json');

    try {
        $tipo = $_POST['tipo'] ?? 'dia';
        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');

        // Calcular fechas según tipo
        switch ($tipo) {
            case 'dia':
                $fechaInicio = $fechaFin = date('Y-m-d');
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

/**
 * Exportar a Excel
 */
public function exportar_excel()
{
    require_once APPROOT . '/../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;

    $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');

    $sesiones = $this->cajaModel->obtenerSesionesCerradas($fechaInicio, $fechaFin);
    $stats = $this->cajaModel->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Título
    $sheet->setCellValue('A1', 'REPORTE DE CIERRES DE CAJA');
    $sheet->mergeCells('A1:J1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Período: ' . $fechaInicio . ' al ' . $fechaFin);
    $sheet->mergeCells('A2:J2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Encabezados
    $headers = ['Turno', 'Cajero', 'Apertura', 'Cierre', 'Inicial', 'Ingresos', 'Egresos', 'Sistema', 'Real', 'Diferencia'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '4', $header);
        $sheet->getStyle($col . '4')->getFont()->setBold(true);
        $sheet->getStyle($col . '4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
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

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Exportar a Word
 */
public function exportar_word()
{
    require_once APPROOT . '/../vendor/autoload.php';
    
    use PhpOffice\PhpWord\PhpWord;

    $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');

    $sesiones = $this->cajaModel->obtenerSesionesCerradas($fechaInicio, $fechaFin);
    $stats = $this->cajaModel->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin);

    $phpWord = new PhpWord();
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

/**
 * Exportar a PDF
 */
public function exportar_pdf($tamano = 'carta')
{
    require_once APPROOT . '/../vendor/autoload.php';
    
    use Dompdf\Dompdf;
    use Dompdf\Options;

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

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper($pageSize, 'portrait');
    $dompdf->render();

    $filename = 'Reporte_Cierres_' . strtoupper($tamano) . '_' . date('Y-m-d_His') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}
```

---

### PARTE B: VISTA - HTML (20 min)

**Archivo:** `c:\xampp\htdocs\venta-pasajes\app\views\caja\index.php`

**1. Agregar botón (línea ~10, dentro del `<div class="col-md-6 text-md-end">`):**

```html
<button class="btn btn-primary btn-lg shadow-sm mb-2" 
        data-bs-toggle="modal" 
        data-bs-target="#modalReportesCierre">
    <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
    REPORTES DE CIERRE
</button>
```

**2. Agregar modal (antes de `</main>`, línea ~126):**

```html
<!-- MODAL REPORTES DE CIERRE -->
<div class="modal fade" id="modalReportesCierre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
                    Reportes de Cierre de Caja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Filtros -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row align-items-end g-2">
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Período Rápido</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-filter-cierre active" data-type="dia">Hoy</button>
                                    <button type="button" class="btn btn-outline-primary btn-filter-cierre" data-type="semana">Semana</button>
                                    <button type="button" class="btn btn-outline-primary btn-filter-cierre" data-type="mes">Mes</button>
                                    <button type="button" class="btn btn-outline-primary btn-filter-cierre" data-type="anio">Año</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Rango Personalizado</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" id="cierre_inicio" class="form-control">
                                    <span class="input-group-text bg-white">-</span>
                                    <input type="date" id="cierre_fin" class="form-control">
                                    <button class="btn btn-secondary" onclick="cargarReporteCierre('rango')">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPIs -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card border-start border-4 border-primary h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Total Sesiones</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_sesiones">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-4 border-success h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Total Sistema</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_sistema">Bs. 0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-4 border-info h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Total Real</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_real">Bs. 0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-4 border-warning h-100 shadow-sm">
                            <div class="card-body py-2">
                                <div class="small text-muted fw-bold text-uppercase">Diferencia</div>
                                <h3 class="fw-bold text-dark mb-0" id="kpi_diferencia">Bs. 0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-striped table-hover mb-0" id="tablaCierres">
                            <thead class="bg-dark text-white text-uppercase small">
                                <tr>
                                    <th class="ps-3">Turno</th>
                                    <th>Cajero</th>
                                    <th>Apertura</th>
                                    <th>Cierre</th>
                                    <th class="text-end">Inicial</th>
                                    <th class="text-end">Ingresos</th>
                                    <th class="text-end">Egresos</th>
                                    <th class="text-end">Sistema</th>
                                    <th class="text-end">Real</th>
                                    <th class="text-end">Diferencia</th>
                                    <th class="text-center pe-3">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tablaCierresBody">
                                <!-- JS INJECT -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <div class="btn-group" role="group">
                    <button class="btn btn-success" onclick="exportarCierreExcel()">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                    <button class="btn btn-primary" onclick="exportarCierreWord()">
                        <i class="bi bi-file-earmark-word me-1"></i> Word
                    </button>
                    <button class="btn btn-danger" onclick="exportarCierrePDF('carta')">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF Carta
                    </button>
                    <button class="btn btn-warning" onclick="exportarCierrePDF('a4')">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF A4
                    </button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
```

---

### PARTE C: VISTA - JAVASCRIPT (20 min)

**Agregar al final del `<script>` existente (antes de `</script>`):**

```javascript
// --- REPORTES DE CIERRE ---
let dataCierreActual = [];

$(document).ready(function() {
    $('#modalReportesCierre').on('shown.bs.modal', function() {
        cargarReporteCierre('dia');
    });

    $('.btn-filter-cierre').click(function() {
        $('.btn-filter-cierre').removeClass('active');
        $(this).addClass('active');
        cargarReporteCierre($(this).data('type'));
    });
});

function cargarReporteCierre(tipo) {
    let inicio = $('#cierre_inicio').val();
    let fin = $('#cierre_fin').val();

    $('#tablaCierresBody').html('<tr><td colspan="11" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');

    $.ajax({
        url: '<?php echo URLROOT; ?>/caja/obtener_reportes_cierre_ajax',
        type: 'POST',
        data: { tipo: tipo, fecha_inicio: inicio, fecha_fin: fin },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                dataCierreActual = res.data;
                renderizarTablaCierres(res.data);
                actualizarKPIsCierre(res.stats);
                $('#cierre_inicio').val(res.periodo.inicio);
                $('#cierre_fin').val(res.periodo.fin);
            } else {
                $('#tablaCierresBody').html('<tr><td colspan="11" class="text-center text-danger py-3">' + res.message + '</td></tr>');
            }
        },
        error: function() {
            $('#tablaCierresBody').html('<tr><td colspan="11" class="text-center text-danger py-3">Error de conexión</td></tr>');
        }
    });
}

function renderizarTablaCierres(data) {
    let html = '';
    if (data.length === 0) {
        html = '<tr><td colspan="11" class="text-center text-muted py-4">No hay cierres en este período</td></tr>';
    } else {
        data.forEach(item => {
            let badgeDif = parseFloat(item.diferencia) === 0 ? '<span class="badge bg-success">PERFECTO</span>' :
                          parseFloat(item.diferencia) > 0 ? '<span class="badge bg-primary">+' + item.diferencia + '</span>' :
                          '<span class="badge bg-danger">' + item.diferencia + '</span>';

            html += `<tr>
                <td class="ps-3">#${item.id}</td>
                <td>${item.cajero_nombre}</td>
                <td class="small">${item.fecha_apertura}</td>
                <td class="small">${item.fecha_cierre}</td>
                <td class="text-end">Bs. ${parseFloat(item.monto_inicial).toFixed(2)}</td>
                <td class="text-end text-success">Bs. ${parseFloat(item.total_ingresos).toFixed(2)}</td>
                <td class="text-end text-danger">Bs. ${parseFloat(item.total_egresos).toFixed(2)}</td>
                <td class="text-end fw-bold">Bs. ${parseFloat(item.monto_final_sistema).toFixed(2)}</td>
                <td class="text-end fw-bold">Bs. ${parseFloat(item.monto_final_real).toFixed(2)}</td>
                <td class="text-end">${badgeDif}</td>
                <td class="text-center"><a href="<?php echo URLROOT; ?>/caja/reporte/${item.id}" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-eye"></i></a></td>
            </tr>`;
        });
    }
    $('#tablaCierresBody').html(html);
}

function actualizarKPIsCierre(stats) {
    $('#kpi_sesiones').text(stats.total_sesiones || 0);
    $('#kpi_sistema').text('Bs. ' + parseFloat(stats.suma_sistema || 0).toFixed(2));
    $('#kpi_real').text('Bs. ' + parseFloat(stats.suma_real || 0).toFixed(2));
    $('#kpi_diferencia').text('Bs. ' + parseFloat(stats.suma_diferencias || 0).toFixed(2));
}

function exportarCierreExcel() {
    if (dataCierreActual.length === 0) {
        Swal.fire('Atención', 'No hay datos para exportar', 'warning');
        return;
    }
    enviarFormExport('<?php echo URLROOT; ?>/caja/exportar_excel');
}

function exportarCierreWord() {
    if (dataCierreActual.length === 0) {
        Swal.fire('Atención', 'No hay datos para exportar', 'warning');
        return;
    }
    enviarFormExport('<?php echo URLROOT; ?>/caja/exportar_word');
}

function exportarCierrePDF(tamano) {
    if (dataCierreActual.length === 0) {
        Swal.fire('Atención', 'No hay datos para exportar', 'warning');
        return;
    }
    enviarFormExport('<?php echo URLROOT; ?>/caja/exportar_pdf/' + tamano);
}

function enviarFormExport(url) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.target = '_blank';

    const inputInicio = document.createElement('input');
    inputInicio.type = 'hidden';
    inputInicio.name = 'fecha_inicio';
    inputInicio.value = $('#cierre_inicio').val();
    form.appendChild(inputInicio);

    const inputFin = document.createElement('input');
    inputFin.type = 'hidden';
    inputFin.name = 'fecha_fin';
    inputFin.value = $('#cierre_fin').val();
    form.appendChild(inputFin);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
```

---

## ✅ VERIFICACIÓN FINAL

### Checklist de Pruebas:
1. [ ] Abrir navegador en `http://localhost/venta-pasajes/caja`
2. [ ] Verificar que aparece el botón "REPORTES DE CIERRE"
3. [ ] Hacer clic en el botón
4. [ ] Verificar que se abre el modal
5. [ ] Probar filtro "Hoy" - debe cargar datos
6. [ ] Probar filtro "Semana"
7. [ ] Probar filtro "Mes"
8. [ ] Probar filtro "Año"
9. [ ] Probar rango personalizado
10. [ ] Verificar que los KPIs se actualizan
11. [ ] Hacer clic en "Excel" - debe descargar archivo .xlsx
12. [ ] Hacer clic en "Word" - debe descargar archivo .docx
13. [ ] Hacer clic en "PDF Carta" - debe descargar PDF
14. [ ] Hacer clic en "PDF A4" - debe descargar PDF
15. [ ] Verificar que los archivos se abren correctamente

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Error: "Class not found"
**Causa:** Dependencias no instaladas  
**Solución:**
```bash
cd c:\xampp\htdocs\venta-pasajes
composer install
```

### Error: "Call to undefined method"
**Causa:** Métodos no agregados al modelo  
**Solución:** Verificar que se copiaron los métodos en `CajaModel.php`

### Modal no se abre
**Causa:** JavaScript no cargado  
**Solución:** Verificar que el código JS está dentro de `<script>` y después de jQuery

### Exportación no funciona
**Causa:** Ruta incorrecta de autoload  
**Solución:** Verificar que `APPROOT . '/../vendor/autoload.php'` es correcto

---

## 📞 AYUDA ADICIONAL

**Archivos de referencia completos:**
- `INFORME_MEJORA_REPORTES_CIERRE_CAJA.md` - Informe técnico detallado
- `RESUMEN_EJECUTIVO_REPORTES_CIERRE.md` - Resumen visual

**¿Necesitas ayuda?** Revisa los archivos de documentación o consulta los logs de PHP en:
`c:\xampp\apache\logs\error.log`

---

*Guía creada el 04/01/2026*
