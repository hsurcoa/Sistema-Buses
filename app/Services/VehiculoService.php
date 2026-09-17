<?php

namespace App\Services;

use App\Models\Vehiculo;
use App\Models\Viaje;
use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/VehiculoModel.php`. */
class VehiculoService
{
    public function registrarVehiculo(array $datos): bool
    {
        return (bool) Vehiculo::create([
            'propietario_nombres' => $datos['propietario_nombres'],
            'propietario_apellidos' => $datos['propietario_apellidos'],
            'tarjeta_circulacion' => $datos['tarjeta_circulacion'],
            'placa' => $datos['placa'],
            'tipo_bus_id' => $datos['tipo_bus_id'] ?: null,
            'soat_numero' => $datos['soat_numero'] ?: null,
            'soat_vencimiento' => $datos['soat_vencimiento'] ?: null,
            'itv_numero' => $datos['itv_numero'] ?: null,
            'itv_vencimiento' => $datos['itv_vencimiento'] ?: null,
            'clase' => $datos['clase'],
            'marca' => $datos['marca'],
            'anio' => $datos['anio'],
            'modelo' => $datos['modelo'],
            'tipo_combustible' => $datos['tipo_combustible'],
            'carroceria' => $datos['carroceria'],
            'ejes' => $datos['ejes'],
            'color' => $datos['color'],
            'nro_motor' => $datos['nro_motor'],
            'cilindros' => $datos['cilindros'],
            'nro_serie' => $datos['nro_serie'],
            'ruedas' => $datos['ruedas'],
            'peso_seco' => $datos['peso_seco'],
            'peso_bruto' => $datos['peso_bruto'],
            'longitud' => $datos['longitud'],
            'altura' => $datos['altura'],
            'ancho' => $datos['ancho'],
            'pasajeros' => $datos['pasajeros'],
            'asientos' => $datos['asientos'],
            'tipo_servicio' => $datos['tipo_servicio'],
            'estado' => 1,
            'fecha_registro' => now(),
        ]);
    }

    public function obtenerVehiculos()
    {
        return Vehiculo::orderByDesc('fecha_registro')->get();
    }

    public function listarVehiculos()
    {
        return DB::table('vehiculos as v')
            ->leftJoin('tipos_buses as tb', 'tb.id', '=', 'v.tipo_bus_id')
            ->where('v.estado', 1)
            ->select('v.*', DB::raw('tb.nombre AS tipo_nombre'), DB::raw('tb.capacidad AS tipo_capacidad'), DB::raw('tb.pisos AS tipo_pisos'))
            ->orderBy('v.placa')
            ->get();
    }

    public function obtenerTipoBus(int $id): ?object
    {
        return DB::table('tipos_buses')->where('id', $id)->select('id', 'nombre', 'capacidad', 'pisos', 'estado')->first();
    }

    public function existePlacaEnOtro(string $placa, int $excluirId = 0): bool
    {
        return Vehiculo::where('placa', $placa)->where('id', '<>', $excluirId)->exists();
    }

    /** Mayor numero de asiento vendido/reservado en viajes aun no realizados con este bus. */
    public function maxAsientoOcupadoEnViajesPendientes(int $vehiculoId): int
    {
        $max = DB::table('boletos as b')
            ->join('viajes as vi', 'vi.id', '=', 'b.viaje_id')
            ->where('vi.bus_id', $vehiculoId)
            ->whereRaw("LOWER(vi.estado) NOT IN ('finalizado', 'cancelado', 'inactivo')")
            ->whereIn('b.estado', ['vendido', 'reservado'])
            ->max('b.numero_asiento');

        return (int) $max;
    }

    /** Viajes pendientes con este bus: al cambiarle el tipo se actualiza su distribucion. */
    public function sincronizarTipoEnViajesPendientes(int $vehiculoId, ?int $tipoBusId): bool
    {
        return (bool) Viaje::where('bus_id', $vehiculoId)
            ->whereRaw("LOWER(estado) NOT IN ('finalizado', 'cancelado', 'inactivo')")
            ->update(['tipo_bus_id' => $tipoBusId]);
    }

    public function obtenerBusPorId(int $id): ?Vehiculo
    {
        return Vehiculo::find($id);
    }

    public function actualizarBus(array $datos): bool
    {
        return (bool) Vehiculo::where('id', $datos['id'])->update([
            'propietario_nombres' => $datos['propietario_nombres'],
            'propietario_apellidos' => $datos['propietario_apellidos'],
            'tarjeta_circulacion' => $datos['tarjeta_circulacion'],
            'placa' => $datos['placa'],
            'tipo_bus_id' => $datos['tipo_bus_id'] ?: null,
            'soat_numero' => $datos['soat_numero'] ?: null,
            'soat_vencimiento' => $datos['soat_vencimiento'] ?: null,
            'itv_numero' => $datos['itv_numero'] ?: null,
            'itv_vencimiento' => $datos['itv_vencimiento'] ?: null,
            'clase' => $datos['clase'],
            'marca' => $datos['marca'],
            'anio' => $datos['anio'],
            'modelo' => $datos['modelo'],
            'tipo_combustible' => $datos['tipo_combustible'],
            'carroceria' => $datos['carroceria'],
            'ejes' => $datos['ejes'],
            'color' => $datos['color'],
            'nro_motor' => $datos['nro_motor'],
            'cilindros' => $datos['cilindros'],
            'nro_serie' => $datos['nro_serie'],
            'ruedas' => $datos['ruedas'],
            'peso_seco' => $datos['peso_seco'],
            'peso_bruto' => $datos['peso_bruto'],
            'longitud' => $datos['longitud'],
            'altura' => $datos['altura'],
            'ancho' => $datos['ancho'],
            'pasajeros' => $datos['pasajeros'],
            'asientos' => $datos['asientos'],
            'tipo_servicio' => $datos['tipo_servicio'],
        ]);
    }

    /**
     * Buses con SOAT/ITV vencidos o por vencer dentro de `$diasAviso` dias.
     * Solo se llama si el toggle `alertas_flota_documentos_activo` esta
     * prendido (ver ConfiguracionService) -- pensado para el banner de
     * Registrar Buses, mismo criterio critico/aviso que ya usa esa pantalla.
     */
    public function alertasDocumentos(int $diasAviso = 30): array
    {
        $limite = now()->addDays($diasAviso)->toDateString();
        $hoy = now()->toDateString();

        $vehiculos = Vehiculo::where('estado', 1)
            ->where(function ($q) use ($limite) {
                $q->whereNotNull('soat_vencimiento')->where('soat_vencimiento', '<=', $limite)
                    ->orWhere(function ($q2) use ($limite) {
                        $q2->whereNotNull('itv_vencimiento')->where('itv_vencimiento', '<=', $limite);
                    });
            })
            ->get(['id', 'placa', 'soat_vencimiento', 'itv_vencimiento']);

        $criticos = [];
        $avisos = [];
        foreach ($vehiculos as $v) {
            foreach ([['SOAT', $v->soat_vencimiento], ['ITV', $v->itv_vencimiento]] as [$doc, $vencimiento]) {
                if (! $vencimiento) {
                    continue;
                }
                $fecha = $vencimiento->toDateString();
                if ($fecha < $hoy) {
                    $criticos[] = ['id' => $v->id, 'placa' => $v->placa, 'texto' => "{$doc} vencido el ".$vencimiento->format('d/m/Y')." ({$v->placa})."];
                } elseif ($fecha <= $limite) {
                    $avisos[] = ['id' => $v->id, 'placa' => $v->placa, 'texto' => "{$doc} vence el ".$vencimiento->format('d/m/Y')." ({$v->placa})."];
                }
            }
        }

        return ['criticos' => $criticos, 'avisos' => $avisos];
    }

    public function eliminarBus(int $id): bool
    {
        return (bool) Vehiculo::where('id', $id)->update(['estado' => 0]);
    }
}
