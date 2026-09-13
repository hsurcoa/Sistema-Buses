# 📊 INFORME TÉCNICO: MEJORA DEL SISTEMA DE REPORTES DE CIERRE DE CAJA

**Fecha:** 04 de Enero de 2026  
**Analista:** Asistente de Desarrollo  
**Módulo:** Control de Caja - Sistema de Reportes  
**Prioridad:** ALTA

---

## 📸 ANÁLISIS DE LA IMAGEN PROPORCIONADA

### Estado Actual del Sistema
La imagen muestra un **Comprobante de Cierre de Caja** con la siguiente estructura:

- **Encabezado:** "COMPROBANTE DE CIERRE - ARQUEO DE CAJA - CONTROL DIARIO"
- **Información del Cajero:** Henrry Ivan Surco Alvan (ID Cajero: 1)
- **Turno:** #2
- **Fechas:** 
  - Apertura: 04/01/2026 09:58
  - Cierre: 04/01/2026 19:00
- **Resumen Financiero:**
  - Fondo Inicial: Bs. 30.00
  - Ventas/Ingresos: Bs. 761.00
  - Gastos/Egresos: Bs. 0.00
  - Total Sistema: Bs. 791.00

### Funcionalidad Actual
✅ **Implementado:**
- Vista de reporte individual de cierre de caja
- Botón "Imprimir Reporte Z" (impresión directa)
- Cálculo automático de diferencias de arqueo
- Modal de reportes de ingresos con filtros (día, semana, mes, año)
- Exportación básica a Excel y PDF (solo para ingresos)

❌ **Faltante (Solicitado):**
- Botón para acceder a reportes consolidados de cierre de caja
- Reportes de cierre agrupados por período (día, semana, mes, año)
- Exportación de reportes de cierre a Excel, Word, PDF Carta y PDF A4
- Interfaz unificada para gestionar todos los reportes

---

## 🎯 OBJETIVO DE LA MEJORA

Implementar un **sistema completo de reportes de cierre de caja** que permita:

1. **Visualizar reportes consolidados** de cierres de caja por período
2. **Filtrar por rangos de tiempo** (día, semana, mes, año, personalizado)
3. **Exportar a múltiples formatos:**
   - 📊 Excel (.xlsx)
   - 📄 Word (.docx)
   - 📕 PDF Tamaño Carta (8.5" x 11")
   - 📗 PDF Tamaño A4 (210mm x 297mm)

---

## 🏗️ ARQUITECTURA DE LA SOLUCIÓN

### 1. COMPONENTES A DESARROLLAR

#### **A. Base de Datos (Sin cambios necesarios)**
✅ La estructura actual de `cajas_sesiones` y `movimientos_caja` es suficiente.

#### **B. Modelo (CajaModel.php)**
📝 **Nuevos métodos a agregar:**

```php
// Obtener todas las sesiones cerradas con filtro de fecha
public function obtenerSesionesCerradas($fechaInicio, $fechaFin)

// Obtener estadísticas consolidadas por período
public function obtenerEstadisticasPeriodo($fechaInicio, $fechaFin)

// Obtener detalles completos de múltiples sesiones
public function obtenerDetallesSesiones($sesionIds)
```

#### **C. Controlador (Caja.php)**
📝 **Nuevos métodos a agregar:**

```php
// Endpoint AJAX para obtener reportes de cierre
public function obtener_reportes_cierre_ajax()

// Exportar a Excel
public function exportar_excel()

// Exportar a Word
public function exportar_word()

// Exportar a PDF (con parámetro de tamaño)
public function exportar_pdf($tamano = 'carta')
```

#### **D. Vista (caja/index.php)**
📝 **Modificaciones:**

1. **Agregar botón "REPORTES DE CIERRE"** en el panel principal
2. **Crear modal `#modalReportesCierre`** con:
   - Filtros de período (día, semana, mes, año, personalizado)
   - Tabla de sesiones cerradas
   - KPIs consolidados
   - Botones de exportación (Excel, Word, PDF Carta, PDF A4)

#### **E. Librerías Necesarias**
📦 **Dependencias a instalar vía Composer:**

```json
{
    "require": {
        "dompdf/dompdf": "^2.0",           // ✅ Ya instalado
        "phpoffice/phpspreadsheet": "^1.29", // ⚠️ Para Excel
        "phpoffice/phpword": "^1.1"         // ⚠️ Para Word
    }
}
```

---

## 📋 DISEÑO DE LA INTERFAZ

### 1. UBICACIÓN DEL BOTÓN PRINCIPAL

**Opción Recomendada:** Agregar un botón flotante o en el header del módulo de caja.

```html
<!-- En caja/index.php, línea ~10 -->
<div class="col-md-6 text-md-end">
    <button class="btn btn-primary btn-lg shadow-sm mb-2" 
            data-bs-toggle="modal" 
            data-bs-target="#modalReportesCierre">
        <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
        REPORTES DE CIERRE
    </button>
    <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-sm">
        <i class="bi bi-check-circle-fill me-1"></i> CAJA ABIERTA
    </span>
    <!-- ... resto del código ... -->
</div>
```

### 2. DISEÑO DEL MODAL DE REPORTES

```
┌─────────────────────────────────────────────────────────────┐
│  📊 REPORTES DE CIERRE DE CAJA                         [X]  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [Filtros]                                                  │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Período: [Hoy] [Semana] [Mes] [Año] [Personalizado]│   │
│  │ Desde: [____] Hasta: [____] [Buscar]               │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  [KPIs Consolidados]                                        │
│  ┌──────────┬──────────┬──────────┬──────────┐            │
│  │ Total    │ Sesiones │ Promedio │ Diferenc.│            │
│  │ Bs.X,XXX │    XX    │ Bs. XXX  │ Bs. ±XX  │            │
│  └──────────┴──────────┴──────────┴──────────┘            │
│                                                             │
│  [Tabla de Sesiones]                                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Turno│Cajero│Apertura│Cierre│Sistema│Real│Dif│     │   │
│  ├─────────────────────────────────────────────────────┤   │
│  │  #2  │ Henrry│04/01 │04/01 │791.00│791│ 0 │ [Ver]│   │
│  │  #1  │ Admin │03/01 │03/01 │450.00│450│ 0 │ [Ver]│   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  [Exportar]                                                 │
│  [📊 Excel] [📄 Word] [📕 PDF Carta] [📗 PDF A4]          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 💻 IMPLEMENTACIÓN TÉCNICA DETALLADA

### FASE 1: ACTUALIZAR MODELO (CajaModel.php)

```php
/**
 * Obtener sesiones cerradas en un rango de fechas
 * @param string $fechaInicio Formato: Y-m-d
 * @param string $fechaFin Formato: Y-m-d
 * @return array Lista de sesiones con datos del cajero
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

### FASE 2: ACTUALIZAR CONTROLADOR (Caja.php)

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
 * Exportar a Excel usando PhpSpreadsheet
 */
public function exportar_excel()
{
    require_once '../vendor/autoload.php';
    
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

    // Encabezado
    $sheet->setCellValue('A1', 'REPORTE DE CIERRES DE CAJA');
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Período: ' . $fechaInicio . ' al ' . $fechaFin);
    $sheet->mergeCells('A2:I2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Cabeceras de tabla
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
        $sheet->setCellValue('A' . $row, $sesion->id);
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
 * Exportar a Word usando PhpWord
 */
public function exportar_word()
{
    require_once '../vendor/autoload.php';
    
    use PhpOffice\PhpWord\PhpWord;
    use PhpOffice\PhpWord\Style\Font;

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
 * Exportar a PDF con tamaño configurable
 * @param string $tamano 'carta' o 'a4'
 */
public function exportar_pdf($tamano = 'carta')
{
    require_once '../vendor/autoload.php';
    
    use Dompdf\Dompdf;
    use Dompdf\Options;

    $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
    $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-d');

    $sesiones = $this->cajaModel->obtenerSesionesCerradas($fechaInicio, $fechaFin);
    $stats = $this->cajaModel->obtenerEstadisticasPeriodo($fechaInicio, $fechaFin);

    // Configurar tamaño de página
    $pageSize = ($tamano === 'a4') ? 'A4' : 'letter';

    // HTML del reporte
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 10pt; }
            h1 { text-align: center; color: #1F4E78; margin-bottom: 5px; }
            h2 { text-align: center; font-size: 12pt; color: #666; margin-top: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #4472C4; color: white; padding: 8px; text-align: left; }
            td { border: 1px solid #ddd; padding: 6px; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .totals { background-color: #E7E6E6; font-weight: bold; }
            .text-right { text-align: right; }
            .summary { margin-top: 30px; padding: 15px; background-color: #F2F2F2; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>REPORTE DE CIERRES DE CAJA</h1>
        <h2>Período: ' . date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin)) . '</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Turno</th>
                    <th>Cajero</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th class="text-right">Inicial</th>
                    <th class="text-right">Ingresos</th>
                    <th class="text-right">Egresos</th>
                    <th class="text-right">Sistema</th>
                    <th class="text-right">Real</th>
                    <th class="text-right">Diferencia</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($sesiones as $sesion) {
        $html .= '
                <tr>
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

    $html .= '
                <tr class="totals">
                    <td colspan="4">TOTALES (' . $stats->total_sesiones . ' sesiones)</td>
                    <td class="text-right">Bs. ' . number_format($stats->suma_inicial, 2) . '</td>
                    <td colspan="2"></td>
                    <td class="text-right">Bs. ' . number_format($stats->suma_sistema, 2) . '</td>
                    <td class="text-right">Bs. ' . number_format($stats->suma_real, 2) . '</td>
                    <td class="text-right">Bs. ' . number_format($stats->suma_diferencias, 2) . '</td>
                </tr>
            </tbody>
        </table>

        <div class="summary">
            <h3>RESUMEN CONSOLIDADO</h3>
            <p><strong>Total de sesiones:</strong> ' . $stats->total_sesiones . '</p>
            <p><strong>Promedio por sesión:</strong> Bs. ' . number_format($stats->promedio_sistema, 2) . '</p>
            <p><strong>Diferencia acumulada:</strong> Bs. ' . number_format($stats->suma_diferencias, 2) . '</p>
        </div>

        <p style="text-align: center; margin-top: 30px; color: #999; font-size: 9pt;">
            Generado el ' . date('d/m/Y H:i:s') . ' | Tamaño: ' . strtoupper($tamano) . '
        </p>
    </body>
    </html>';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

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

### FASE 3: ACTUALIZAR VISTA (caja/index.php)

#### **A. Agregar botón en el header (después de línea 10)**

```html
<div class="col-md-6 text-md-end">
    <!-- NUEVO BOTÓN -->
    <button class="btn btn-primary btn-lg shadow-sm mb-2" 
            data-bs-toggle="modal" 
            data-bs-target="#modalReportesCierre">
        <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>
        REPORTES DE CIERRE
    </button>
    
    <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-sm">
        <i class="bi bi-check-circle-fill me-1"></i> CAJA ABIERTA
    </span>
    <!-- ... resto del código ... -->
</div>
```

#### **B. Agregar modal completo (antes del cierre de `</main>`)**

```html
<!-- MODAL REPORTES DE CIERRE DE CAJA -->
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

#### **C. Agregar JavaScript (antes del cierre de `</script>`)**

```javascript
// --- LÓGICA DE REPORTES DE CIERRE ---
let dataCierreActual = []; // Almacenar datos para exportación

$(document).ready(function() {
    // Cargar reporte del día al abrir modal
    $('#modalReportesCierre').on('shown.bs.modal', function() {
        cargarReporteCierre('dia');
    });

    // Botones de filtro
    $('.btn-filter-cierre').click(function() {
        $('.btn-filter-cierre').removeClass('active');
        $(this).addClass('active');
        const type = $(this).data('type');
        cargarReporteCierre(type);
    });
});

function cargarReporteCierre(tipo) {
    let inicio = $('#cierre_inicio').val();
    let fin = $('#cierre_fin').val();

    // Mostrar Loading...
    $('#tablaCierresBody').html('<tr><td colspan="11" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');

    $.ajax({
        url: '<?php echo URLROOT; ?>/caja/obtener_reportes_cierre_ajax',
        type: 'POST',
        data: {
            tipo: tipo,
            fecha_inicio: inicio,
            fecha_fin: fin
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                dataCierreActual = res.data; // Guardar para exportación
                renderizarTablaCierres(res.data);
                actualizarKPIsCierre(res.stats);
                
                // Actualizar campos de fecha
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
        html = '<tr><td colspan="11" class="text-center text-muted py-4">No se encontraron cierres en este período.</td></tr>';
    } else {
        data.forEach(item => {
            let badgeDif = '';
            if (parseFloat(item.diferencia) === 0) {
                badgeDif = '<span class="badge bg-success">PERFECTO</span>';
            } else if (parseFloat(item.diferencia) > 0) {
                badgeDif = '<span class="badge bg-primary">+' + item.diferencia + '</span>';
            } else {
                badgeDif = '<span class="badge bg-danger">' + item.diferencia + '</span>';
            }

            html += `
                <tr>
                    <td class="ps-3 align-middle">#${item.id}</td>
                    <td class="align-middle">${item.cajero_nombre}</td>
                    <td class="align-middle small">${item.fecha_apertura}</td>
                    <td class="align-middle small">${item.fecha_cierre}</td>
                    <td class="text-end align-middle">Bs. ${parseFloat(item.monto_inicial).toFixed(2)}</td>
                    <td class="text-end align-middle text-success">Bs. ${parseFloat(item.total_ingresos).toFixed(2)}</td>
                    <td class="text-end align-middle text-danger">Bs. ${parseFloat(item.total_egresos).toFixed(2)}</td>
                    <td class="text-end align-middle fw-bold">Bs. ${parseFloat(item.monto_final_sistema).toFixed(2)}</td>
                    <td class="text-end align-middle fw-bold">Bs. ${parseFloat(item.monto_final_real).toFixed(2)}</td>
                    <td class="text-end align-middle">${badgeDif}</td>
                    <td class="text-center align-middle pe-3">
                        <a href="<?php echo URLROOT; ?>/caja/reporte/${item.id}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            `;
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

// --- FUNCIONES DE EXPORTACIÓN ---
function exportarCierreExcel() {
    if (dataCierreActual.length === 0) {
        Swal.fire('Atención', 'No hay datos para exportar', 'warning');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo URLROOT; ?>/caja/exportar_excel';
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

function exportarCierreWord() {
    if (dataCierreActual.length === 0) {
        Swal.fire('Atención', 'No hay datos para exportar', 'warning');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo URLROOT; ?>/caja/exportar_word';
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

function exportarCierrePDF(tamano) {
    if (dataCierreActual.length === 0) {
        Swal.fire('Atención', 'No hay datos para exportar', 'warning');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo URLROOT; ?>/caja/exportar_pdf/' + tamano;
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

## 📦 INSTALACIÓN DE DEPENDENCIAS

### Comando a ejecutar:

```bash
cd c:\xampp\htdocs\venta-pasajes
composer require phpoffice/phpspreadsheet:^1.29
composer require phpoffice/phpword:^1.1
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Fase 1: Preparación
- [ ] Instalar dependencias de Composer (PhpSpreadsheet, PhpWord)
- [ ] Verificar que DomPDF esté instalado correctamente
- [ ] Hacer backup de la base de datos

### Fase 2: Backend
- [ ] Agregar métodos al modelo `CajaModel.php`:
  - [ ] `obtenerSesionesCerradas()`
  - [ ] `obtenerEstadisticasPeriodo()`
- [ ] Agregar métodos al controlador `Caja.php`:
  - [ ] `obtener_reportes_cierre_ajax()`
  - [ ] `exportar_excel()`
  - [ ] `exportar_word()`
  - [ ] `exportar_pdf()`

### Fase 3: Frontend
- [ ] Agregar botón "REPORTES DE CIERRE" en `caja/index.php`
- [ ] Crear modal `#modalReportesCierre`
- [ ] Agregar JavaScript para carga de datos
- [ ] Agregar funciones de exportación

### Fase 4: Pruebas
- [ ] Probar filtros (día, semana, mes, año, personalizado)
- [ ] Verificar cálculo de KPIs
- [ ] Probar exportación a Excel
- [ ] Probar exportación a Word
- [ ] Probar exportación a PDF Carta
- [ ] Probar exportación a PDF A4
- [ ] Verificar diseño responsive

---

## 🎨 MEJORAS VISUALES SUGERIDAS

### 1. Iconos Diferenciadores
```html
<!-- En la tabla de cierres -->
<td class="text-end align-middle">
    <?php if ($diferencia == 0): ?>
        <i class="bi bi-check-circle-fill text-success me-1"></i>
    <?php elseif ($diferencia > 0): ?>
        <i class="bi bi-arrow-up-circle-fill text-primary me-1"></i>
    <?php else: ?>
        <i class="bi bi-arrow-down-circle-fill text-danger me-1"></i>
    <?php endif; ?>
    Bs. <?php echo number_format($diferencia, 2); ?>
</td>
```

### 2. Animaciones de Carga
```css
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

#tablaCierresBody tr {
    animation: fadeIn 0.3s ease-in-out;
}
```

### 3. Tooltips Informativos
```html
<button class="btn btn-success" 
        onclick="exportarCierreExcel()"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="Exportar a formato Excel (.xlsx)">
    <i class="bi bi-file-earmark-excel me-1"></i> Excel
</button>
```

---

## 🔒 CONSIDERACIONES DE SEGURIDAD

1. **Validación de Permisos:**
   - Solo usuarios con rol de Administrador o Cajero deben acceder a reportes
   - Implementar verificación de sesión en cada endpoint

2. **Sanitización de Datos:**
   - Validar fechas antes de consultas SQL
   - Usar prepared statements (ya implementado en el modelo)

3. **Control de Acceso:**
   ```php
   // En Caja.php, inicio de cada método de exportación
   if (!isset($_SESSION['user_id'])) {
       header('Location: ' . URLROOT . '/login');
       exit;
   }
   ```

---

## 📊 MÉTRICAS DE ÉXITO

### Indicadores de Implementación Exitosa:
- ✅ Tiempo de carga del modal < 2 segundos
- ✅ Exportación de archivos < 5 segundos
- ✅ Interfaz responsive en dispositivos móviles
- ✅ Datos consistentes entre vista y exportaciones
- ✅ Sin errores de PHP/JavaScript en consola

---

## 🚀 ROADMAP DE IMPLEMENTACIÓN

### Semana 1: Backend
- Días 1-2: Instalación de dependencias y configuración
- Días 3-4: Desarrollo de métodos del modelo
- Días 5-7: Desarrollo de métodos del controlador

### Semana 2: Frontend
- Días 1-3: Diseño e implementación del modal
- Días 4-5: Integración con backend (AJAX)
- Días 6-7: Funciones de exportación

### Semana 3: Pruebas y Ajustes
- Días 1-3: Pruebas funcionales
- Días 4-5: Ajustes de diseño y UX
- Días 6-7: Documentación y capacitación

---

## 📝 NOTAS ADICIONALES

### Alternativas de Exportación

#### **Para Excel:**
- **Opción 1:** PhpSpreadsheet (Recomendado) - Más completo y moderno
- **Opción 2:** PHPExcel (Deprecated) - No recomendado
- **Opción 3:** CSV simple - Más rápido pero menos funcional

#### **Para Word:**
- **Opción 1:** PhpWord (Recomendado) - Genera .docx nativos
- **Opción 2:** RTF - Compatible pero limitado
- **Opción 3:** HTML to DOCX - Requiere conversión adicional

#### **Para PDF:**
- **Opción 1:** DomPDF (Ya instalado) - Bueno para layouts simples
- **Opción 2:** TCPDF - Más control pero más complejo
- **Opción 3:** mPDF - Balance entre funcionalidad y facilidad

### Optimizaciones Futuras

1. **Caché de Reportes:**
   - Implementar sistema de caché para reportes frecuentes
   - Reducir carga en base de datos

2. **Exportación Asíncrona:**
   - Para reportes grandes, usar cola de trabajos
   - Notificar al usuario cuando el archivo esté listo

3. **Gráficos Estadísticos:**
   - Integrar Chart.js para visualizaciones
   - Mostrar tendencias de cierres en el tiempo

4. **Envío por Email:**
   - Opción de enviar reportes directamente por correo
   - Programar envíos automáticos

---

## 🎯 CONCLUSIÓN

La implementación propuesta es **completa, escalable y profesional**. Incluye:

✅ **Backend robusto** con métodos bien estructurados  
✅ **Frontend moderno** con diseño responsive  
✅ **Múltiples formatos de exportación** (Excel, Word, PDF Carta, PDF A4)  
✅ **Filtros flexibles** (día, semana, mes, año, personalizado)  
✅ **KPIs consolidados** para análisis rápido  
✅ **Seguridad** con validaciones y control de acceso  

**Tiempo estimado de implementación:** 2-3 semanas  
**Nivel de complejidad:** Medio-Alto  
**Impacto en el sistema:** Alto (mejora significativa en reportería)

---

## 📞 SOPORTE Y CONSULTAS

Para dudas o ajustes durante la implementación, consultar:
- Documentación de PhpSpreadsheet: https://phpspreadsheet.readthedocs.io/
- Documentación de PhpWord: https://phpword.readthedocs.io/
- Documentación de DomPDF: https://github.com/dompdf/dompdf

---

**Fin del Informe**  
*Documento generado el 04/01/2026*
