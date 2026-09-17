<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/EncomiendaModel.php`. */
class EncomiendaService
{
    public function obtenerTarifaPorRuta(int $rutaId): ?object
    {
        return DB::table('tarifas_encomienda')->where('ruta_id', $rutaId)->where('estado', 1)->first();
    }

    public function listarEncomiendas(int $limit = 100, int $offset = 0)
    {
        return DB::table('encomiendas as e')
            ->join('viajes as v', 'e.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->leftJoin('detalles_encomienda as d', 'd.encomienda_id', '=', 'e.id')
            ->select('e.*', 'v.fecha_salida', 'r.origen', 'r.destino', DB::raw('d.descripcion as contenido_breve'))
            ->orderByDesc('e.id')
            ->limit($limit)->offset($offset)
            ->get();
    }

    /** Registrar una nueva encomienda con validaciones de negocio y transaccion (caja + detalle + cabecera). */
    public function registrarEncomienda(array $datos): array
    {
        try {
            return DB::transaction(function () use ($datos) {
                $cajaAbierta = DB::table('cajas_sesiones')->where('usuario_id', $datos['usuario_id'])->where('estado', 'ABIERTA')->first();
                if (! $cajaAbierta) {
                    throw new \Exception('No tienes una Caja Abierta. Debes abrir caja para registrar encomiendas.');
                }
                $sesionId = $cajaAbierta->id;

                $viaje = DB::table('viajes')->where('id', $datos['viaje_id'])->select('id', 'ruta_id', 'estado', 'bus_id')->first();
                if (! $viaje) {
                    throw new \Exception('El viaje seleccionado no existe');
                }
                if ($viaje->estado == 'Finalizado' || $viaje->estado == 'Cancelado') {
                    throw new \Exception('No se pueden registrar encomiendas en viajes finalizados o cancelados');
                }

                $capacidadMax = 1000; // Default 1 tonelada si no hay dato.
                $pesoActual = DB::table('detalles_encomienda as de')
                    ->join('encomiendas as e', 'de.encomienda_id', '=', 'e.id')
                    ->where('e.viaje_id', $datos['viaje_id'])->where('e.estado', '<>', 'CANCELADO')
                    ->sum('peso_kg') ?? 0;

                if (($pesoActual + $datos['peso']) > $capacidadMax) {
                    throw new \Exception('Capacidad de bodega excedida. Disponible: '.($capacidadMax - $pesoActual).'kg');
                }

                $totalBase = $datos['precio_verificado'] ?? 0;
                $porcentajeSeguro = 2.00;
                $costoSeguro = $datos['valor_declarado'] * ($porcentajeSeguro / 100);
                $totalCalculado = $totalBase + $costoSeguro;

                $codigoGuia = $this->generarCodigoGuia();

                $encomiendaId = DB::table('encomiendas')->insertGetId([
                    'codigo_guia' => $codigoGuia,
                    'viaje_id' => $datos['viaje_id'],
                    'remitente_nombre' => $datos['remitente_nombre'],
                    'remitente_dni' => $datos['remitente_dni'],
                    'destinatario_nombre' => $datos['destinatario_nombre'],
                    'destinatario_dni' => $datos['destinatario_dni'],
                    'destinatario_telefono' => $datos['destinatario_telefono'],
                    'clave_retiro' => $datos['clave_retiro'],
                    'usuario_creacion_id' => $datos['usuario_id'],
                    'total_pagar' => $totalCalculado,
                    'estado' => 'REGISTRADO',
                    'fecha_registro' => now(),
                ]);

                DB::table('detalles_encomienda')->insert([
                    'encomienda_id' => $encomiendaId,
                    'descripcion' => $datos['descripcion'],
                    'peso_kg' => $datos['peso'],
                    'tipo_carga' => $datos['tipo_carga'],
                    'valor_declarado' => $datos['valor_declarado'],
                    'precio_calculado' => $totalCalculado,
                ]);

                DB::table('movimientos_caja')->insert([
                    'sesion_id' => $sesionId,
                    'tipo_movimiento' => 'INGRESO',
                    'origen_modulo' => 'ENCOMIENDA',
                    'referencia_id' => $encomiendaId,
                    'monto' => $totalCalculado,
                    'descripcion' => 'Encomienda Guía #'.$codigoGuia,
                    'fecha_creacion' => now(),
                ]);

                return ['status' => true, 'id' => $encomiendaId, 'guia' => $codigoGuia, 'total' => $totalCalculado];
            });
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function generarCodigoGuia(): string
    {
        return 'ENC-'.date('Y').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function obtenerEncomiendaPorId(int $id): ?object
    {
        return DB::table('encomiendas as e')
            ->leftJoin('detalles_encomienda as d', 'd.encomienda_id', '=', 'e.id')
            ->join('viajes as v', 'e.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->where('e.id', $id)
            ->select('e.*', 'd.*', 'v.fecha_salida', 'r.origen', 'r.destino')
            ->first();
    }

    public function listarViajesFuturosPorRuta(int $rutaId)
    {
        return DB::table('viajes as v')
            ->leftJoin('vehiculos as b', 'v.bus_id', '=', 'b.id')
            ->where('v.ruta_id', $rutaId)
            ->whereIn('v.estado', ['Programado', 'Activo'])
            ->whereRaw("CONCAT(v.fecha_salida, ' ', v.hora_salida) > NOW()")
            ->select('v.id', 'v.fecha_salida', 'v.hora_salida', 'b.placa')
            ->orderBy('v.fecha_salida')
            ->get();
    }
}
