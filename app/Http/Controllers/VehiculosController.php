<?php

namespace App\Http\Controllers;

use App\Services\VehiculoService;
use Illuminate\Http\Request;

/**
 * CRUD de buses (la vista `admin/registrar_buses` llama a `/vehiculos/*`,
 * no a `/admin/*` — ver `AdminController::registrarBuses()`). Puerto de
 * `legacy/app/controllers/Vehiculos.php` (Fase 5; reescrito a Eloquent al
 * cerrar la sesion).
 */
class VehiculosController extends Controller
{
    public function __construct(private VehiculoService $vehiculos) {}

    private const MARCAS = [
        'TOYOTA' => 'Toyota', 'TOYOYA' => 'Toyota', 'MERCEDES' => 'Mercedes-Benz', 'MERCEDES BENZ' => 'Mercedes-Benz',
        'MERCEDES-BENZ' => 'Mercedes-Benz', 'VOLVO' => 'Volvo', 'SCANIA' => 'Scania', 'MARCOPOLO' => 'Marcopolo',
        'HYUNDAI' => 'Hyundai', 'VOLKSWAGEN' => 'Volkswagen', 'VW' => 'Volkswagen', 'MAN' => 'MAN', 'IVECO' => 'Iveco',
        'YUTONG' => 'Yutong', 'KING LONG' => 'King Long', 'HIGER' => 'Higer', 'NISSAN' => 'Nissan', 'MITSUBISHI' => 'Mitsubishi',
        'HINO' => 'Hino', 'IRIZAR' => 'Irizar', 'BUSSCAR' => 'Busscar', 'COMIL' => 'Comil', 'FOTON' => 'Foton', 'JAC' => 'JAC',
    ];

    /** Normaliza y valida los datos de un bus. Devuelve el mensaje de error o null. */
    private function normalizarYValidar(array &$datos, int $id): ?string
    {
        $datos['placa'] = strtoupper(preg_replace('/\s+/', '', $datos['placa']));
        if (! preg_match('/^[A-Z0-9]{2,4}-?[A-Z0-9]{2,4}$/', $datos['placa'])) {
            return 'La placa "'.$datos['placa'].'" no es válida. Use letras y números, p. ej. 2345-ABC o ABC-123.';
        }
        if ($this->vehiculos->existePlacaEnOtro($datos['placa'], $id)) {
            return 'La placa '.$datos['placa'].' ya está registrada en otro bus.';
        }

        if (empty($datos['tipo_bus_id'])) {
            return 'Seleccione el tipo de bus: define la cantidad y distribución de asientos.';
        }
        $tipo = $this->vehiculos->obtenerTipoBus((int) $datos['tipo_bus_id']);
        if (! $tipo) {
            return 'El tipo de bus seleccionado no existe.';
        }
        $datos['asientos'] = (int) $tipo->capacidad;
        if (empty($datos['pasajeros']) || (int) $datos['pasajeros'] < $datos['asientos']) {
            $datos['pasajeros'] = $datos['asientos'];
        }

        if ($id) {
            $maxOcupado = $this->vehiculos->maxAsientoOcupadoEnViajesPendientes($id);
            if ($maxOcupado > $datos['asientos']) {
                return "No se puede usar un tipo de {$datos['asientos']} asientos: este bus tiene viajes pendientes con el asiento {$maxOcupado} ya vendido o reservado.";
            }
        }

        $marca = strtoupper(trim(preg_replace('/\s+/', ' ', $datos['marca'])));
        if ($marca !== '') {
            $datos['marca'] = self::MARCAS[$marca] ?? mb_convert_case(mb_strtolower($marca), MB_CASE_TITLE, 'UTF-8');
        }

        $datos['modelo'] = trim($datos['modelo']);
        if (preg_match('/^(19|20)\d{2}$/', $datos['modelo'])) {
            return 'El modelo "'.$datos['modelo'].'" parece un año. Escriba el modelo de la carrocería o chasis (p. ej. Paradiso 1800, Coaster) y el año en el campo Año.';
        }

        if ($datos['anio'] !== '') {
            $anio = (int) $datos['anio'];
            if ($anio < 1970 || $anio > (int) date('Y') + 1) {
                return 'El año del bus debe estar entre 1970 y '.((int) date('Y') + 1).'.';
            }
        }

        return null;
    }

    private function datosDelRequest(Request $request): array
    {
        return [
            'propietario_nombres' => trim($request->input('propietario_nombres', '')),
            'propietario_apellidos' => trim($request->input('propietario_apellidos', '')),
            'tarjeta_circulacion' => trim($request->input('tarjeta_circulacion', '')),
            'placa' => trim($request->input('placa', '')),
            'tipo_bus_id' => trim($request->input('tipo_bus_id', '')),
            'clase' => trim($request->input('clase', '')),
            'marca' => trim($request->input('marca', '')),
            'anio' => trim($request->input('anio', '')),
            'modelo' => trim($request->input('modelo', '')),
            'tipo_combustible' => trim($request->input('tipo_combustible', '')),
            'carroceria' => trim($request->input('carroceria', '')),
            'ejes' => trim($request->input('ejes', '')),
            'color' => trim($request->input('color', '')),
            'nro_motor' => trim($request->input('nro_motor', '')),
            'cilindros' => trim($request->input('cilindros', '')),
            'nro_serie' => trim($request->input('nro_serie', '')),
            'ruedas' => trim($request->input('ruedas', '')),
            'peso_seco' => trim($request->input('peso_seco', '')),
            'peso_bruto' => trim($request->input('peso_bruto', '')),
            'longitud' => trim($request->input('longitud', '')),
            'altura' => trim($request->input('altura', '')),
            'ancho' => trim($request->input('ancho', '')),
            'pasajeros' => trim($request->input('pasajeros', '')),
            'asientos' => trim($request->input('asientos', '')),
            'tipo_servicio' => trim($request->input('tipo_servicio', '')),
        ];
    }

    public function guardar(Request $request)
    {
        $datos = $this->datosDelRequest($request);

        if ($datos['placa'] === '' || $datos['tarjeta_circulacion'] === '' || $datos['tipo_bus_id'] === '') {
            return response()->json(['status' => 'error', 'message' => 'Los campos Placa, Tarjeta de Circulación y Tipo de Bus son obligatorios.']);
        }

        if ($error = $this->normalizarYValidar($datos, 0)) {
            return response()->json(['status' => 'error', 'message' => $error]);
        }

        try {
            return $this->vehiculos->registrarVehiculo($datos)
                ? response()->json(['status' => 'success', 'message' => 'El bus ha sido registrado correctamente en el sistema.', 'placa' => $datos['placa']])
                : response()->json(['status' => 'error', 'message' => 'Ocurrió un error al registrar el bus. Por favor, intente nuevamente.']);
        } catch (\Exception $e) {
            $msg = str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), 'Duplicate')
                ? 'La placa ingresada ya se encuentra registrada en el sistema.'
                : 'Error al registrar el bus.';

            return response()->json(['status' => 'error', 'message' => $msg]);
        }
    }

    public function generarPdf()
    {
        $data = [
            'vehiculos' => $this->vehiculos->obtenerVehiculos(),
            'fecha_generacion' => date('d/m/Y H:i:s'),
        ];

        ob_start();
        require resource_path('views/admin/pdf_buses.php');
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();
        $dompdf->stream('reporte_flota_'.date('Ymd_His').'.pdf', ['Attachment' => true]);
        exit;
    }

    public function obtenerBus(Request $request)
    {
        $id = trim($request->input('id', ''));
        if ($id === '') {
            return response()->json(['status' => 'error', 'message' => 'No se proporcionó el ID del bus.']);
        }

        $bus = $this->vehiculos->obtenerBusPorId((int) $id);

        return $bus
            ? response()->json(['status' => 'success', 'bus' => $bus])
            : response()->json(['status' => 'error', 'message' => 'No se encontró el bus con el ID proporcionado.']);
    }

    public function editarBus(Request $request)
    {
        $datos = $this->datosDelRequest($request);
        $datos['id'] = trim($request->input('id', ''));

        if ($datos['id'] === '' || $datos['placa'] === '' || $datos['tarjeta_circulacion'] === '') {
            return response()->json(['status' => 'error', 'message' => 'Los campos ID, Placa y Tarjeta de Circulación son obligatorios.']);
        }

        if ($error = $this->normalizarYValidar($datos, (int) $datos['id'])) {
            return response()->json(['status' => 'error', 'message' => $error]);
        }

        try {
            if ($this->vehiculos->actualizarBus($datos)) {
                $this->vehiculos->sincronizarTipoEnViajesPendientes((int) $datos['id'], (int) $datos['tipo_bus_id']);

                return response()->json(['status' => 'success', 'message' => 'El bus ha sido actualizado correctamente.', 'placa' => $datos['placa']]);
            }

            return response()->json(['status' => 'error', 'message' => 'Ocurrió un error al actualizar el bus. Por favor, intente nuevamente.']);
        } catch (\Exception $e) {
            $msg = str_contains($e->getMessage(), '23000') || str_contains($e->getMessage(), 'Duplicate')
                ? 'La placa ingresada ya se encuentra registrada en el sistema.'
                : 'Error al actualizar el bus.';

            return response()->json(['status' => 'error', 'message' => $msg]);
        }
    }

    public function eliminarBus(Request $request)
    {
        $id = trim($request->input('id', ''));
        if ($id === '') {
            return response()->json(['status' => 'error', 'message' => 'No se proporcionó el ID del bus.']);
        }

        try {
            return $this->vehiculos->eliminarBus((int) $id)
                ? response()->json(['status' => 'success', 'message' => 'El bus ha sido eliminado correctamente del sistema.'])
                : response()->json(['status' => 'error', 'message' => 'No se pudo eliminar el bus. Por favor, intente nuevamente.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error al eliminar el bus: '.$e->getMessage()]);
        }
    }
}
