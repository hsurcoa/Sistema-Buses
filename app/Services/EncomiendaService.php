<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Reescritura Eloquent de `legacy/app/models/EncomiendaModel.php`. */
class EncomiendaService
{
    /** Orden real del ciclo de vida de una encomienda (coincide con el enum de la columna). */
    private const ORDEN_ESTADOS = ['REGISTRADO', 'EN_ALMACEN_ORIGEN', 'EN_RUTA', 'EN_DESTINO', 'ENTREGADO'];

    public function obtenerTarifaPorRuta(int $rutaId): ?object
    {
        return DB::table('tarifas_encomienda')->where('ruta_id', $rutaId)->where('estado', 1)->first();
    }

    /**
     * `$sucursalId` filtra por sucursal de ORIGEN o de DESTINO (una sucursal
     * necesita ver tanto lo que registro para enviar como lo que le esta
     * llegando para entregar). Null = todas (Administrador/Supervisor).
     */
    public function listarEncomiendas(?int $sucursalId = null, int $limit = 100, int $offset = 0)
    {
        $q = DB::table('encomiendas as e')
            ->join('viajes as v', 'e.viaje_id', '=', 'v.id')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->leftJoin('detalles_encomienda as d', 'd.encomienda_id', '=', 'e.id')
            ->leftJoin('terminales as to_suc', 'e.sucursal_origen_id', '=', 'to_suc.id')
            ->leftJoin('terminales as td_suc', 'e.sucursal_destino_id', '=', 'td_suc.id')
            ->select('e.*', 'v.fecha_salida', 'r.origen', 'r.destino', DB::raw('d.descripcion as contenido_breve'),
                DB::raw('to_suc.nombre_sede as sucursal_origen_nombre'), DB::raw('td_suc.nombre_sede as sucursal_destino_nombre'))
            ->orderByDesc('e.id')
            ->limit($limit)->offset($offset);

        if ($sucursalId) {
            $q->where(function ($w) use ($sucursalId) {
                $w->where('e.sucursal_origen_id', $sucursalId)->orWhere('e.sucursal_destino_id', $sucursalId);
            });
        }

        return $q->get();
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

                $viaje = DB::table('viajes')->where('id', $datos['viaje_id'])
                    ->select('id', 'ruta_id', 'estado', 'bus_id', 'terminal_destino_id')->first();
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

                // Origen = sucursal donde se registra/paga (la caja abierta del
                // vendedor, igual criterio que boletos.sucursal_id). Destino =
                // terminal de llegada del viaje elegido: es donde el
                // destinatario la retira. Sin esto (bug encontrado en la
                // auditoria multisucursal) las dos quedaban siempre NULL y no
                // se podia saber de donde a donde viajaba cada encomienda.
                $encomiendaId = DB::table('encomiendas')->insertGetId([
                    'codigo_guia' => $codigoGuia,
                    'viaje_id' => $datos['viaje_id'],
                    'sucursal_origen_id' => $cajaAbierta->sucursal_id,
                    'sucursal_destino_id' => $viaje->terminal_destino_id,
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
                    'tipo_paquete_id' => $datos['tipo_paquete_id'] ?: null,
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

    /**
     * Avanza el estado de una encomienda (REGISTRADO → EN_ALMACEN_ORIGEN →
     * EN_RUTA → EN_DESTINO → ENTREGADO). Nunca retrocede ni salta hacia
     * atras, y una ya ENTREGADO queda bloqueada. Para marcar ENTREGADO, si
     * la encomienda tiene clave de retiro cargada, hay que confirmarla
     * (evita que cualquiera la marque como entregada sin verificar al
     * destinatario).
     */
    public function cambiarEstado(int $id, string $nuevoEstado, ?string $claveRetiro = null): array
    {
        if (! in_array($nuevoEstado, self::ORDEN_ESTADOS, true)) {
            return ['status' => false, 'message' => 'Estado no válido.'];
        }

        $enc = DB::table('encomiendas')->where('id', $id)->first();
        if (! $enc) {
            return ['status' => false, 'message' => 'Encomienda no encontrada.'];
        }

        if ($enc->estado === 'ENTREGADO') {
            return ['status' => false, 'message' => 'Esta encomienda ya fue entregada.'];
        }

        $idxActual = array_search($enc->estado, self::ORDEN_ESTADOS, true);
        $idxNuevo = array_search($nuevoEstado, self::ORDEN_ESTADOS, true);
        if ($idxNuevo === false || $idxNuevo <= $idxActual) {
            return ['status' => false, 'message' => 'No se puede retroceder el estado de una encomienda.'];
        }

        if ($nuevoEstado === 'ENTREGADO' && ! empty($enc->clave_retiro)) {
            if (trim((string) $claveRetiro) !== $enc->clave_retiro) {
                return ['status' => false, 'message' => 'La clave de retiro no coincide.'];
            }
        }

        DB::table('encomiendas')->where('id', $id)->update(['estado' => $nuevoEstado]);

        return ['status' => true, 'estado' => $nuevoEstado];
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
            ->leftJoin('terminales as to_suc', 'e.sucursal_origen_id', '=', 'to_suc.id')
            ->leftJoin('terminales as td_suc', 'e.sucursal_destino_id', '=', 'td_suc.id')
            ->where('e.id', $id)
            ->select('e.*', 'd.*', 'v.fecha_salida', 'r.origen', 'r.destino',
                DB::raw('to_suc.nombre_sede as sucursal_origen_nombre'), DB::raw('td_suc.nombre_sede as sucursal_destino_nombre'))
            ->first();
    }

    public function listarViajesFuturosPorRuta(int $rutaId)
    {
        // v.fecha_salida ya es un DATETIME completo (incluye la hora); concatenarlo
        // con v.hora_salida producia un string invalido ("...12:00:00 12:00:00")
        // que MySQL no podia comparar contra NOW(), asi que esto nunca devolvia
        // ningun viaje aunque estuvieran programados a futuro.
        return DB::table('viajes as v')
            ->leftJoin('vehiculos as b', 'v.bus_id', '=', 'b.id')
            ->leftJoin('tipos_buses as tb', 'v.tipo_bus_id', '=', 'tb.id')
            ->where('v.ruta_id', $rutaId)
            ->whereIn('v.estado', ['Programado', 'Activo'])
            ->where('v.fecha_salida', '>', now())
            ->select('v.id', 'v.fecha_salida', 'v.hora_salida', 'b.placa',
                DB::raw('tb.nombre as tipo_bus'), DB::raw('tb.capacidad as capacidad_pasajeros'))
            ->orderBy('v.fecha_salida')
            ->get();
    }
}
