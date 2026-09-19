<?php

namespace App\Services;

use App\Models\Boleto;
use App\Models\Cliente;
use App\Models\LibelulaCobro;
use App\Models\Personal;
use App\Models\Ruta;
use App\Models\Vehiculo;
use App\Models\Viaje;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rutas, viajes, venta/reserva de boletos y cobro QR. Reescritura Eloquent
 * de `legacy/app/models/RutaModel.php` (1398 lineas de PHP puro con PDO
 * manual) — pieza mas critica de la migracion: `registrarVentaTransaccion()`
 * es el flujo de dinero real (venta de pasajes), con bloqueo de fila para
 * evitar doble venta del mismo asiento.
 *
 * Metodos del original comprobados como codigo muerto (sin ningun call site
 * en los controladores ya migrados) NO se portaron: `contarRutas`,
 * `buscarRutas`, `buscarOCrearCliente` (privado), `actualizarReserva`,
 * `actualizarAsignacionBus` (privado), `guardarParada`/`eliminarParada`
 * (singular — el flujo real usa `TramoService::guardarParadas` en plural).
 */
class RutaService
{
    public function __construct(private TramoService $tramos, private LibelulaService $libelula) {}

    public function listarRutas(int $limit = 100, int $offset = 0)
    {
        return Ruta::orderByDesc('id')->limit($limit)->offset($offset)->get();
    }

    public function listarRutasActivas()
    {
        return Ruta::where('estado', 1)->orderBy('origen')->orderBy('destino')->get();
    }

    public function obtenerRutaPorId(int $id): ?Ruta
    {
        return Ruta::find($id);
    }

    /** @return true|string true si se registro, mensaje de error si no. */
    public function registrarRuta(string $origen, string $destino): true|string
    {
        if (strtoupper(trim($origen)) === strtoupper(trim($destino))) {
            return 'El origen y el destino no pueden ser iguales';
        }
        if (trim($origen) === '' || trim($destino) === '') {
            return 'El origen y el destino son obligatorios';
        }

        Ruta::create(['origen' => $origen, 'destino' => $destino, 'estado' => 1]);

        return true;
    }

    /** @return true|string */
    public function actualizarRuta(int $id, string $origen, string $destino, int $estado = 1): true|string
    {
        if (strtoupper(trim($origen)) === strtoupper(trim($destino))) {
            return 'El origen y el destino no pueden ser iguales';
        }

        Ruta::where('id', $id)->update(['origen' => $origen, 'destino' => $destino, 'estado' => $estado]);

        return true;
    }

    /** @return true|string */
    public function eliminarRuta(int $id): true|string
    {
        $viajes = Viaje::where('ruta_id', $id)->count();
        if ($viajes > 0) {
            return "No se puede eliminar la ruta porque tiene {$viajes} viaje(s) asociado(s). Desactívela en su lugar.";
        }

        Ruta::where('id', $id)->delete();

        return true;
    }

    public function cambiarEstado(int $id, int $estado): bool
    {
        return (bool) Ruta::where('id', $id)->update(['estado' => $estado]);
    }

    /**
     * Asientos ocupados de un viaje para el tramo pedido (0/0 = ruta completa).
     * @return array simple de numeros de asiento y su tramo/estado.
     */
    public function obtenerAsientosOcupados(int $viajeId, int $subidaId = 0, int $bajadaId = 0): array
    {
        try {
            $this->liberarCobrosQrVencidos($viajeId);

            $rutaId = (int) (Viaje::where('id', $viajeId)->value('ruta_id') ?? 0);
            $ordenSube = $this->tramos->orden($rutaId, $subidaId, false) ?? 0;
            $ordenBaja = $this->tramos->orden($rutaId, $bajadaId, true) ?? TramoService::DESTINO;

            return $this->tramos->asientosOcupados($viajeId, $ordenSube, $ordenBaja);
        } catch (\Exception $e) {
            Log::error('RutaService::obtenerAsientosOcupados: '.$e->getMessage());

            return [];
        }
    }

    /** @return true|string */
    public function guardarRutaViaje(array $data): true|string
    {
        try {
            if (empty($data['ruta_id']) || empty($data['tipo_bus_id']) || empty($data['fecha_salida'])) {
                return 'Faltan campos obligatorios';
            }
            if (! empty($data['terminal_origen_id']) && ! empty($data['terminal_destino_id'])
                && $data['terminal_origen_id'] == $data['terminal_destino_id']) {
                return 'La terminal de origen y destino deben ser diferentes';
            }

            $serviciosIncluidos = isset($data['servicios']) && is_array($data['servicios']) ? json_encode($data['servicios']) : null;

            $fechaSegura = date('Y-m-d', strtotime(str_replace('/', '-', $data['fecha_salida'])));
            $fechaHoraSalida = $fechaSegura.(! empty($data['hora_salida']) ? ' '.$data['hora_salida'] : ' 00:00:00');

            $fechaHoraLlegada = null;
            if (! empty($data['hora_llegada'])) {
                $fechaHoraLlegada = $fechaSegura.' '.$data['hora_llegada'];
                if (! empty($data['hora_salida']) && strtotime($data['hora_llegada']) < strtotime($data['hora_salida'])) {
                    $fechaHoraLlegada = date('Y-m-d H:i:s', strtotime($fechaSegura.' +1 day '.$data['hora_llegada']));
                }
            }

            $busId = ! empty($data['bus_id']) ? $data['bus_id'] : null;
            $choferId = ! empty($data['chofer_id']) ? $data['chofer_id'] : null;

            if ($busId) {
                // El mapa de asientos debe ser el del bus real: el tipo del viaje sale del bus.
                $bus = Vehiculo::find($busId);
                if (! $bus || ! $bus->estado) {
                    return 'El bus seleccionado no existe o está dado de baja.';
                }
                if (! $bus->tipo_bus_id) {
                    return "El bus {$bus->placa} no tiene tipo de bus (asientos) definido. Complételo en Gestión de Flota.";
                }
                $data['tipo_bus_id'] = $bus->tipo_bus_id;

                if (! $choferId) {
                    $choferId = DB::table('asignaciones_buses')->where('bus_id', $busId)->where('estado', 1)->value('chofer_id');
                }
            }

            if (! empty($data['id'])) {
                // No achicar el bus por debajo de un asiento ya vendido/reservado.
                $maxAsiento = (int) (Boleto::where('viaje_id', $data['id'])->whereIn('estado', ['vendido', 'reservado'])->max('numero_asiento') ?? 0);
                $capacidad = (int) DB::table('tipos_buses')->where('id', $data['tipo_bus_id'])->value('capacidad');
                if ($maxAsiento > $capacidad) {
                    return "No se puede cambiar a un bus de {$capacidad} asientos: el viaje ya tiene vendido o reservado el asiento {$maxAsiento}.";
                }
            }

            if ($choferId) {
                $chofer = Personal::find($choferId);
                if (! $chofer || ! $chofer->estado || $chofer->perfil !== 'Chofer') {
                    return 'El conductor seleccionado no es un chofer activo del personal.';
                }

                $choque = Viaje::join('rutas', 'rutas.id', '=', 'viajes.ruta_id')
                    ->where('viajes.chofer_id', $choferId)
                    ->where('viajes.fecha_salida', $fechaHoraSalida)
                    ->where('viajes.id', '<>', (int) ($data['id'] ?? 0))
                    ->whereRaw("LOWER(viajes.estado) NOT IN ('finalizado', 'cancelado')")
                    ->select('viajes.id', 'rutas.origen', 'rutas.destino')
                    ->first();

                if ($choque) {
                    return "{$chofer->nombres} {$chofer->apellidos} ya sale a esa hora en el viaje #{$choque->id} ({$choque->origen} - {$choque->destino}).";
                }
            }

            $payload = [
                'ruta_id' => $data['ruta_id'],
                'tipo_bus_id' => $data['tipo_bus_id'],
                'terminal_origen_id' => $data['terminal_origen_id'] ?? null,
                'terminal_destino_id' => $data['terminal_destino_id'] ?? null,
                'bus_id' => $busId,
                'chofer_id' => $choferId,
                'fecha_salida' => $fechaHoraSalida,
                'hora_salida' => $data['hora_salida'] ?? null,
                'fecha_llegada_estimada' => $fechaHoraLlegada,
                'hora_llegada' => $data['hora_llegada'] ?? null,
                'precio_base' => $data['precio_base'] ?? 0,
                'tipo_servicio' => $data['tipo_servicio'] ?? 'Ejecutivo',
                'servicios_incluidos' => $serviciosIncluidos,
                'notas' => $data['notas'],
                'estado' => $data['estado'] ?? 'Programado',
            ];

            if (! empty($data['id'])) {
                Viaje::where('id', $data['id'])->update($payload);
            } else {
                Viaje::create($payload);
            }

            return true;
        } catch (\PDOException|\Illuminate\Database\QueryException $e) {
            return 'Error de base de datos: '.$e->getMessage();
        }
    }

    /** Buses activos de un tipo, con su tripulacion por defecto. */
    public function obtenerBusesPorTipo(int $tipoBusId)
    {
        return DB::table('vehiculos as v')
            ->leftJoin('asignaciones_buses as ab', function ($j) {
                $j->on('ab.bus_id', '=', 'v.id')->where('ab.estado', 1);
            })
            ->leftJoin('personal as c', 'c.id', '=', 'ab.chofer_id')
            ->where('v.estado', 1)->where('v.tipo_bus_id', $tipoBusId)
            ->select(['v.id', 'v.placa', 'v.marca', 'v.modelo', 'v.asientos', 'v.tipo_servicio', DB::raw('v.id AS numero_interno'), DB::raw("CONCAT(c.nombres, ' ', c.apellidos) AS chofer_asignado")])
            ->orderBy('v.placa')
            ->get();
    }

    /** Datos del bus y su tripulacion por defecto. */
    public function obtenerTripulacionBus(int $busId)
    {
        try {
            return DB::table('vehiculos as v')
                ->leftJoin('tipos_buses as tb', 'tb.id', '=', 'v.tipo_bus_id')
                ->leftJoin('asignaciones_buses as ab', function ($j) {
                    $j->on('ab.bus_id', '=', 'v.id')->where('ab.estado', 1);
                })
                ->leftJoin('personal as c', 'c.id', '=', 'ab.chofer_id')
                ->leftJoin('personal as cp', 'cp.id', '=', 'ab.copiloto_id')
                ->where('v.id', $busId)
                ->select([
                    DB::raw('v.id AS bus_id'), 'ab.chofer_id',
                    DB::raw("CONCAT(c.nombres, ' ', c.apellidos) AS nombre_chofer"),
                    'ab.copiloto_id',
                    DB::raw("CONCAT(cp.nombres, ' ', cp.apellidos) AS nombre_copiloto"),
                    DB::raw('v.placa AS bus_placa'), DB::raw('v.id AS bus_numero'),
                    DB::raw('v.marca AS bus_marca'), DB::raw('v.modelo AS bus_modelo'),
                    DB::raw('COALESCE(tb.nombre, v.tipo_servicio) AS tipo_bus'),
                    DB::raw('COALESCE(tb.pisos, 1) AS bus_pisos'),
                    DB::raw('COALESCE(tb.capacidad, v.asientos) AS bus_capacidad'),
                ])
                ->first();
        } catch (\Exception $e) {
            Log::error('RutaService::obtenerTripulacionBus: '.$e->getMessage());

            return false;
        }
    }

    /** Viajes programados (no finalizados/cancelados) con informacion completa. */
    public function listarViajesProgramados(int $limit = 100, int $offset = 0)
    {
        try {
            return DB::table('viajes as v')
                ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
                ->leftJoin('tipos_buses as tb', 'v.tipo_bus_id', '=', 'tb.id')
                ->leftJoin('terminales as to_term', 'v.terminal_origen_id', '=', 'to_term.id')
                ->leftJoin('terminales as td_term', 'v.terminal_destino_id', '=', 'td_term.id')
                ->leftJoin('personal as u1', 'v.chofer_id', '=', 'u1.id')
                ->leftJoin('vehiculos as b', 'v.bus_id', '=', 'b.id')
                // LOWER() por normalizacion-bd hallazgo 6: el filtro exacto
                // capitalizado se rompia en silencio si estado se guardaba en
                // otro casing (ver docs/superpowers/plans/2026-09-17-auditoria-normalizacion-bd.md).
                ->whereRaw("LOWER(v.estado) NOT IN ('finalizado', 'cancelado')")
                ->select([
                    'v.id', 'v.fecha_salida', 'v.hora_salida', 'v.hora_llegada', 'v.precio_base',
                    'v.tipo_servicio', 'v.estado', 'r.origen', 'r.destino',
                    DB::raw('tb.nombre as tipo_bus'), 'tb.capacidad',
                    DB::raw('to_term.nombre_sede as terminal_origen'), DB::raw('td_term.nombre_sede as terminal_destino'),
                    DB::raw("CONCAT(u1.nombres, ' ', u1.apellidos) as chofer_nombre"),
                    DB::raw('b.placa as bus_placa'), DB::raw('b.id as bus_numero'),
                ])
                ->orderBy('v.fecha_salida')->orderBy('v.hora_salida')
                ->limit($limit)->offset($offset)
                ->get();
        } catch (\Exception $e) {
            Log::error('RutaService::listarViajesProgramados: '.$e->getMessage());

            return collect();
        }
    }

    /** Despachar bus: finaliza el viaje actual y crea uno nuevo (clon) para reutilizar el bus. */
    public function despacharYCrearNuevo(int $viajeId): int|false
    {
        try {
            return DB::transaction(function () use ($viajeId) {
                $viajeActual = $this->obtenerViajePorId($viajeId);
                if (! $viajeActual) {
                    throw new \Exception('Viaje no encontrado');
                }

                Viaje::where('id', $viajeId)->update(['estado' => 'Finalizado']);

                $nuevaFecha = date('Y-m-d', strtotime($viajeActual->fecha_salida.' +1 day'));

                $nuevo = Viaje::create([
                    'ruta_id' => $viajeActual->ruta_id,
                    'tipo_bus_id' => $viajeActual->tipo_bus_id,
                    'bus_id' => $viajeActual->bus_id,
                    'chofer_id' => $viajeActual->chofer_id,
                    'terminal_origen_id' => $viajeActual->terminal_origen_id,
                    'terminal_destino_id' => $viajeActual->terminal_destino_id,
                    'fecha_salida' => $nuevaFecha,
                    'hora_salida' => $viajeActual->hora_salida,
                    'hora_llegada' => $viajeActual->hora_llegada,
                    'precio_base' => $viajeActual->precio_base,
                    'tipo_servicio' => $viajeActual->tipo_servicio,
                    'servicios_incluidos' => $viajeActual->servicios_incluidos,
                    'notas' => 'Viaje generado automáticamente desde despacho',
                    'estado' => 'Programado',
                ]);

                return (int) $nuevo->id;
            });
        } catch (\Exception $e) {
            Log::error('RutaService::despacharYCrearNuevo: '.$e->getMessage());

            return false;
        }
    }

    public function cambiarEstadoViaje(int $id, string $nuevoEstado): bool
    {
        try {
            return (bool) Viaje::where('id', $id)->update(['estado' => $nuevoEstado]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function obtenerViajePorId(int $id): ?object
    {
        return DB::table('viajes as v')
            ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
            ->leftJoin('tipos_buses as tb', 'v.tipo_bus_id', '=', 'tb.id')
            ->leftJoin('terminales as term_origen', 'v.terminal_origen_id', '=', 'term_origen.id')
            ->leftJoin('terminales as term_destino', 'v.terminal_destino_id', '=', 'term_destino.id')
            ->leftJoin('vehiculos as b', 'v.bus_id', '=', 'b.id')
            ->leftJoin('personal as p_viaje', 'v.chofer_id', '=', 'p_viaje.id')
            ->leftJoin('asignaciones_buses as ab', function ($j) {
                $j->on('b.id', '=', 'ab.bus_id')->where('ab.estado', 1);
            })
            ->leftJoin('personal as p_copiloto', 'ab.copiloto_id', '=', 'p_copiloto.id')
            ->leftJoin('personal as p_chofer', 'ab.chofer_id', '=', 'p_chofer.id')
            ->where('v.id', $id)
            ->select([
                'v.*', 'r.origen', 'r.destino',
                DB::raw('tb.nombre as tipo_bus'), DB::raw('COALESCE(tb.capacidad, 0) as capacidad'),
                DB::raw("COALESCE(term_origen.nombre_sede, 'Sin Asignar') as terminal_origen"),
                DB::raw("COALESCE(term_destino.nombre_sede, 'Sin Asignar') as terminal_destino"),
                DB::raw("COALESCE(CONCAT(p_viaje.nombres, ' ', p_viaje.apellidos), CONCAT(p_chofer.nombres, ' ', p_chofer.apellidos), 'PENDIENTE DE ASIGNACIÓN') as chofer_nombre"),
                DB::raw("COALESCE(CONCAT(p_copiloto.nombres, ' ', p_copiloto.apellidos), 'PENDIENTE DE ASIGNACIÓN') as copiloto_nombre"),
                DB::raw("COALESCE(b.placa, 'PENDIENTE DE ASIGNACIÓN') as bus_placa"),
                DB::raw("COALESCE(b.id, 'S/N') as bus_numero"),
            ])
            ->first();
    }

    /**
     * TRANSACCION COMPLETA: registrar venta o reserva de un boleto.
     * Bloquea la fila del viaje (FOR UPDATE) para que dos ventas simultaneas
     * del mismo asiento no pasen ambas — la garantia central de esta clase.
     */
    public function registrarVentaTransaccion(array $datos): object|false
    {
        return DB::transaction(function () use ($datos) {
            // 1. Cliente (buscar o crear/actualizar).
            $cliente = Cliente::where('numero_documento', $datos['documento'])->first();
            if ($cliente) {
                $celular = $datos['celular'];
                if (empty($celular) && ! empty($cliente->celular)) {
                    $celular = $cliente->celular;
                }
                $cliente->update(['nombres' => $datos['nombres'], 'apellidos' => $datos['apellidos'], 'celular' => $celular]);
                $clienteId = $cliente->id;
            } else {
                $clienteId = Cliente::create([
                    'tipo_documento' => 'CI',
                    'numero_documento' => $datos['documento'],
                    'nombres' => $datos['nombres'],
                    'apellidos' => $datos['apellidos'],
                    'celular' => $datos['celular'],
                ])->id;
            }

            // 2. Caja abierta del vendedor (y su sucursal).
            $cajaAbierta = DB::table('cajas_sesiones')->where('usuario_id', $datos['usuario_id'])->where('estado', 'ABIERTA')->first();
            $sucursalVenta = $cajaAbierta->sucursal_id ?? DB::table('usuarios')->where('id', $datos['usuario_id'])->value('sucursal_id');

            if ($datos['estado'] == 'vendido' || ($datos['metodo_pago'] ?? '') === 'QR') {
                if (! $cajaAbierta) {
                    throw new \Exception('No tienes una Caja Abierta. Debes abrir caja para realizar ventas.');
                }
                $sesionId = $datos['estado'] == 'vendido' ? $cajaAbierta->id : null;
            } else {
                $sesionId = null;
            }

            // 2a. El asiento debe existir en el bus del viaje.
            $capacidad = (int) (DB::table('viajes')->leftJoin('tipos_buses', 'tipos_buses.id', '=', 'viajes.tipo_bus_id')
                ->where('viajes.id', $datos['viaje_id'])->value('tipos_buses.capacidad') ?? 0);
            $asiento = (int) $datos['asiento'];
            if ($capacidad <= 0) {
                throw new \Exception("El viaje #{$datos['viaje_id']} no tiene un bus con asientos definidos.");
            }
            if ($asiento < 1 || $asiento > $capacidad) {
                throw new \Exception("El asiento {$datos['asiento']} no existe en este bus (asientos 1 a {$capacidad}).");
            }

            // 2. Verificar disponibilidad EN EL TRAMO, bloqueando la fila del viaje.
            $this->liberarCobrosQrVencidos((int) $datos['viaje_id']);
            $viajeVenta = DB::table('viajes')->where('id', $datos['viaje_id'])->lockForUpdate()->select('id', 'ruta_id', 'precio_base')->first();

            $subidaId = (int) ($datos['parada_subida_id'] ?? 0);
            $bajadaId = (int) ($datos['parada_id'] ?? 0);
            $ordenSube = $this->tramos->orden((int) $viajeVenta->ruta_id, $subidaId, false);
            $ordenBaja = $this->tramos->orden((int) $viajeVenta->ruta_id, $bajadaId, true);
            if ($ordenSube === null || $ordenBaja === null) {
                throw new \Exception('La parada de subida o de bajada no pertenece a la ruta de este viaje.');
            }
            if ($ordenSube >= $ordenBaja) {
                throw new \Exception('La parada de bajada debe estar después de la de subida.');
            }

            $otros = DB::table('boletos as b')
                ->leftJoin('rutas_paradas as ps', 'ps.id', '=', 'b.parada_subida_id')
                ->leftJoin('rutas_paradas as pb', 'pb.id', '=', 'b.parada_id')
                ->where('b.viaje_id', $datos['viaje_id'])->where('b.numero_asiento', $datos['asiento'])
                ->whereIn('b.estado', ['vendido', 'reservado'])
                ->select(['b.id', DB::raw('COALESCE(ps.orden_index, 0) AS orden_sube'), DB::raw('COALESCE(pb.orden_index, '.TramoService::DESTINO.') AS orden_baja')])
                ->lockForUpdate()
                ->get();

            foreach ($otros as $otro) {
                if ((int) $otro->orden_sube < $ordenBaja && $ordenSube < (int) $otro->orden_baja) {
                    throw new \Exception('El asiento '.$datos['asiento']." ya está ocupado en ese tramo del viaje #".$datos['viaje_id'].'. Seleccione otro.');
                }
            }

            // Precio del tramo segun tarifa (el navegador no decide el precio).
            $tarifa = $this->tramos->tarifa((int) $viajeVenta->ruta_id, $subidaId, $bajadaId);
            $precio = $datos['precio'] ?? 0;
            if ($tarifa !== null) {
                $precio = $tarifa;
            } elseif ($subidaId === 0 && $bajadaId === 0) {
                $precio = $viajeVenta->precio_base;
            }
            if ((float) $precio <= 0) {
                throw new \Exception('El tramo elegido no tiene tarifa. Configúrela en Rutas y tarifas.');
            }

            // 3. Codigo: correlativo de la sucursal (EAL-000123).
            $codigo = $sucursalVenta ? $this->siguienteCodigoBoleto($sucursalVenta) : null;
            $codigo = $codigo ?: $this->generarCodigoBoleto();

            $metodoPago = ($datos['metodo_pago'] ?? 'EFECTIVO') === 'QR' ? 'QR' : 'EFECTIVO';
            $minutosQr = ! empty($datos['minutos_reserva']) ? max(1, (int) $datos['minutos_reserva']) : null;

            $boletoId = DB::table('boletos')->insertGetId([
                'viaje_id' => $datos['viaje_id'],
                'cliente_id' => $clienteId,
                'usuario_vendedor_id' => $datos['usuario_id'],
                'sesion_caja_id' => $sesionId,
                'sucursal_id' => $sucursalVenta,
                'parada_subida_id' => $subidaId ?: null,
                'numero_asiento' => $datos['asiento'],
                'precio_final' => $precio,
                'metodo_pago' => $metodoPago,
                'fecha_pago' => $datos['estado'] == 'vendido' ? now() : null,
                'estado' => $datos['estado'],
                'fecha_reserva' => now(),
                'fecha_expiracion_reserva' => $minutosQr ? now()->addMinutes($minutosQr) : null,
                'codigo_boleto' => $codigo,
                'parada_id' => $bajadaId ?: null,
            ]);

            // 5. Movimiento de caja (solo si es venta).
            if ($datos['estado'] == 'vendido' && $sesionId) {
                DB::table('movimientos_caja')->insert([
                    'sesion_id' => $sesionId,
                    'tipo_movimiento' => 'INGRESO',
                    'origen_modulo' => 'PASAJE',
                    'referencia_id' => $boletoId,
                    'monto' => $precio,
                    'metodo_pago' => $metodoPago,
                    'descripcion' => 'Venta Boleto #'.$codigo.' Asiento: '.$datos['asiento'],
                    'fecha_creacion' => now(),
                ]);
            }

            return $this->obtenerDatosTicket($boletoId);
        });
    }

    /** Siguiente correlativo de la sucursal (boletos), seguro ante ventas simultaneas (misma fila ya bloqueada por la transaccion que llama). */
    private function siguienteCodigoBoleto(int $sucursalId): ?string
    {
        $afectadas = DB::table('terminales')->where('id', $sucursalId)
            ->update(['correlativo_boleto' => DB::raw('LAST_INSERT_ID(correlativo_boleto + 1)')]);
        if (! $afectadas) {
            return null;
        }
        $numero = (int) DB::select('SELECT LAST_INSERT_ID() AS n')[0]->n;
        $prefijo = DB::table('terminales')->where('id', $sucursalId)->value('prefijo_boleto') ?: 'SUC'.$sucursalId;

        return sprintf('%s-%06d', $prefijo, $numero);
    }

    private function generarCodigoBoleto(): string
    {
        return 'BOL-'.date('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function obtenerDatosTicket(int $boletoId): ?object
    {
        try {
            return DB::table('boletos as b')
                ->join('clientes as c', 'b.cliente_id', '=', 'c.id')
                ->join('viajes as v', 'b.viaje_id', '=', 'v.id')
                ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
                ->leftJoin('rutas_paradas as rp', 'b.parada_id', '=', 'rp.id')
                ->leftJoin('rutas_paradas as rps', 'b.parada_subida_id', '=', 'rps.id')
                ->leftJoin('terminales as ter_orig', 'v.terminal_origen_id', '=', 'ter_orig.id')
                ->leftJoin('vehiculos as bus', 'v.bus_id', '=', 'bus.id')
                ->leftJoin('terminales as suc', 'suc.id', '=', 'b.sucursal_id')
                ->where('b.id', $boletoId)
                ->select([
                    DB::raw('b.id as id_boleto'), 'b.codigo_boleto', 'b.numero_asiento',
                    DB::raw('b.precio_final as precio'), DB::raw('b.fecha_reserva as fecha_venta'),
                    DB::raw("CONCAT(c.nombres, ' ', c.apellidos) as nombre_pasajero"), 'c.numero_documento',
                    'v.fecha_salida', 'v.hora_salida',
                    DB::raw('COALESCE(rps.nombre_parada, r.origen) as ciudad_origen'),
                    DB::raw('COALESCE(rp.nombre_parada, r.destino) as ciudad_destino'),
                    DB::raw('ter_orig.nombre_sede as terminal_origen'),
                    DB::raw('bus.placa as bus_placa'), DB::raw('bus.id as bus_numero'),
                    DB::raw('suc.nombre_sede as sucursal_nombre'), DB::raw('suc.direccion as sucursal_direccion'), DB::raw('suc.telefono as sucursal_telefono'),
                    'b.metodo_pago', 'b.referencia_pago', 'b.estado',
                ])
                ->first();
        } catch (\Exception $e) {
            Log::error('RutaService::obtenerDatosTicket: '.$e->getMessage());

            return null;
        }
    }

    public function obtenerPasajerosPorViaje(int $viajeId)
    {
        try {
            return DB::table('boletos as b')
                ->join('clientes as c', 'b.cliente_id', '=', 'c.id')
                ->join('viajes as v', 'b.viaje_id', '=', 'v.id')
                ->join('rutas as r', 'v.ruta_id', '=', 'r.id')
                ->leftJoin('rutas_paradas as rp', 'b.parada_id', '=', 'rp.id')
                ->leftJoin('rutas_paradas as rps', 'b.parada_subida_id', '=', 'rps.id')
                ->leftJoin('vehiculos as bu', 'v.bus_id', '=', 'bu.id')
                ->where('b.viaje_id', $viajeId)
                ->whereIn('b.estado', ['vendido', 'reservado'])
                ->select([
                    'b.id', 'b.numero_asiento', 'b.codigo_boleto', 'c.numero_documento',
                    DB::raw('b.precio_final as precio'), 'b.estado', DB::raw('b.fecha_reserva as fecha_venta'),
                    'c.nombres', 'c.apellidos', DB::raw("CONCAT(c.nombres, ' ', c.apellidos) as nombre_pasajero"),
                    DB::raw('c.celular as telefono'), 'v.fecha_salida', 'v.hora_salida',
                    DB::raw('COALESCE(rps.nombre_parada, r.origen) AS ciudad_origen'),
                    DB::raw('COALESCE(rp.nombre_parada, r.destino) AS ciudad_destino'),
                    DB::raw('COALESCE(rps.nombre_parada, r.origen) AS origen'),
                    DB::raw('COALESCE(rp.nombre_parada, r.destino) AS destino'),
                    DB::raw("COALESCE(bu.placa, 'SIN ASIGNAR') AS bus_placa"),
                ])
                ->orderByRaw('CAST(b.numero_asiento AS UNSIGNED) ASC')
                ->get();
        } catch (\Exception $e) {
            Log::error('RutaService::obtenerPasajerosPorViaje: '.$e->getMessage());

            throw new \Exception('Error al consultar pasajeros: '.$e->getMessage());
        }
    }

    /**
     * Cancela un boleto. Si esta "reservado" (nunca se cobro) se borra sin
     * mas: no hay plata de por medio. Si ya estaba "vendido", NO se borra
     * (quedaria el ingreso en movimientos_caja sin ningun boleto que lo
     * explique, un dinero fantasma en el cierre de caja) -- se marca
     * "cancelado" (libera el asiento igual que un delete, pero deja rastro).
     *
     * Antes esto revertia el ingreso como egreso automaticamente, sin
     * preguntar si el pasajero realmente recibio la plata de vuelta, y lo
     * hacia contra la sesion de caja ORIGINAL de la venta (que puede estar
     * cerrada hace dias -- un egreso ahi corrompe un cierre ya reportado).
     * Ahora: el egreso solo se genera si $devuelto es true, va contra la
     * caja ABIERTA de quien cancela, y SIEMPRE queda un registro en
     * cancelaciones_boletos (haya habido devolucion o no) para que sea
     * trazable. Un cobro QR nunca conto como efectivo en caja, asi que una
     * devolucion sobre un pago QR no genera movimiento de caja.
     *
     * @throws \Exception si hay que devolver en efectivo y el usuario no tiene caja abierta.
     */
    public function cancelarBoleto(int $id, int $usuarioId, ?bool $devuelto = null, ?string $metodoDevolucion = null, ?string $motivo = null): bool
    {
        return DB::transaction(function () use ($id, $usuarioId, $devuelto, $metodoDevolucion, $motivo) {
            $boleto = DB::table('boletos')->where('id', $id)->lockForUpdate()
                ->select('id', 'estado', 'sesion_caja_id', 'precio_final', 'metodo_pago', 'codigo_boleto', 'numero_asiento')
                ->first();

            if (! $boleto) {
                return false;
            }

            if ($boleto->estado !== 'vendido') {
                return (bool) Boleto::where('id', $id)->delete();
            }

            Boleto::where('id', $id)->update(['estado' => 'cancelado']);

            $devuelto = $devuelto === true;
            $sesionCajaId = null;

            if ($devuelto && $boleto->metodo_pago !== 'QR') {
                $caja = DB::table('cajas_sesiones')->where('usuario_id', $usuarioId)->where('estado', 'ABIERTA')->first();
                if (! $caja) {
                    throw new \Exception('Debe tener una caja abierta para procesar la devolución en efectivo.');
                }

                DB::table('movimientos_caja')->insert([
                    'sesion_id' => $caja->id,
                    'tipo_movimiento' => 'EGRESO',
                    'origen_modulo' => 'PASAJE',
                    'referencia_id' => $id,
                    'monto' => $boleto->precio_final,
                    'descripcion' => "Devolución Boleto #{$boleto->codigo_boleto} (asiento {$boleto->numero_asiento})",
                    'fecha_creacion' => now(),
                ]);
                $sesionCajaId = $caja->id;
            }

            DB::table('cancelaciones_boletos')->insert([
                'boleto_id' => $id,
                'usuario_id' => $usuarioId,
                'sesion_caja_id' => $sesionCajaId,
                'monto' => $boleto->precio_final,
                'devuelto' => $devuelto,
                'metodo_devolucion' => $devuelto ? ($metodoDevolucion ?: $boleto->metodo_pago) : null,
                'motivo' => $motivo,
                'usuario_devolucion_id' => $devuelto ? $usuarioId : null,
                'fecha_devolucion' => $devuelto ? now() : null,
                'fecha_creacion' => now(),
            ]);

            return true;
        });
    }

    /** Datos que el frontend necesita para saber si hay que preguntar por la devolución antes de cancelar. */
    public function infoParaCancelar(int $id): ?object
    {
        $boleto = DB::table('boletos')->where('id', $id)->select('estado', 'precio_final', 'metodo_pago')->first();
        if (! $boleto) {
            return null;
        }

        return (object) [
            'requiere_devolucion' => $boleto->estado === 'vendido',
            'monto' => $boleto->precio_final,
            'metodo_pago' => $boleto->metodo_pago,
        ];
    }

    /**
     * Procesa la devolución de un boleto que se canceló antes SIN devolver
     * la plata (el pasajero vuelve despues a reclamarla). Genera el egreso
     * en la caja abierta de quien la procesa recien en este momento.
     *
     * @throws \Exception si el registro no existe, ya tiene devolución, o falta caja abierta para efectivo.
     */
    public function procesarDevolucionPendiente(int $cancelacionId, int $usuarioId, string $metodoDevolucion): bool
    {
        return DB::transaction(function () use ($cancelacionId, $usuarioId, $metodoDevolucion) {
            $cancelacion = DB::table('cancelaciones_boletos')->where('id', $cancelacionId)->lockForUpdate()->first();

            if (! $cancelacion) {
                throw new \Exception('No se encontró el registro de cancelación.');
            }
            if ($cancelacion->devuelto) {
                throw new \Exception('Esta cancelación ya tiene una devolución registrada.');
            }

            $sesionCajaId = null;

            if ($metodoDevolucion === 'EFECTIVO') {
                $caja = DB::table('cajas_sesiones')->where('usuario_id', $usuarioId)->where('estado', 'ABIERTA')->first();
                if (! $caja) {
                    throw new \Exception('Debe tener una caja abierta para procesar la devolución en efectivo.');
                }

                $boleto = DB::table('boletos')->where('id', $cancelacion->boleto_id)->select('codigo_boleto', 'numero_asiento')->first();

                DB::table('movimientos_caja')->insert([
                    'sesion_id' => $caja->id,
                    'tipo_movimiento' => 'EGRESO',
                    'origen_modulo' => 'PASAJE',
                    'referencia_id' => $cancelacion->boleto_id,
                    'monto' => $cancelacion->monto,
                    'descripcion' => 'Devolución Boleto #'.($boleto->codigo_boleto ?? $cancelacion->boleto_id).' (reclamada tras la cancelación)',
                    'fecha_creacion' => now(),
                ]);
                $sesionCajaId = $caja->id;
            }

            DB::table('cancelaciones_boletos')->where('id', $cancelacionId)->update([
                'devuelto' => true,
                'metodo_devolucion' => $metodoDevolucion,
                'sesion_caja_id' => $sesionCajaId,
                'usuario_devolucion_id' => $usuarioId,
                'fecha_devolucion' => now(),
            ]);

            return true;
        });
    }

    public function obtenerTicketPorId(int $id): ?object
    {
        return DB::table('boletos as b')
            ->join('clientes as c', 'b.cliente_id', '=', 'c.id')
            ->where('b.id', $id)
            ->select(['b.id', 'b.numero_asiento', 'b.precio_final', 'b.estado', 'c.nombres', 'c.apellidos', 'c.numero_documento'])
            ->first();
    }

    public function actualizarDatosBoleto(int $boletoId, string $nombres, string $apellidos, $precio, string $documento): bool
    {
        $clienteId = Boleto::where('id', $boletoId)->value('cliente_id');
        if (! $clienteId) {
            return false;
        }
        Cliente::where('id', $clienteId)->update(['nombres' => $nombres, 'apellidos' => $apellidos, 'numero_documento' => $documento]);

        return (bool) Boleto::where('id', $boletoId)->update(['precio_final' => $precio]);
    }

    /** Confirma el pago de un boleto reservado (efectivo o QR) y lo registra en la caja abierta del usuario. */
    public function confirmarPagoBoleto(int $boletoId, int $usuarioId, string $metodo = 'EFECTIVO', ?string $referencia = null): ?object
    {
        $metodo = $metodo === 'QR' ? 'QR' : 'EFECTIVO';

        DB::transaction(function () use ($boletoId, $usuarioId, $metodo, $referencia) {
            $boleto = DB::table('boletos')->where('id', $boletoId)->lockForUpdate()
                ->select('id', 'estado', 'precio_final', 'codigo_boleto', 'numero_asiento', 'viaje_id')->first();

            if (! $boleto) {
                throw new \Exception('El boleto no existe.');
            }
            if ($boleto->estado === 'vendido') {
                throw new \Exception('Este boleto ya fue cobrado.');
            }
            if ($boleto->estado !== 'reservado') {
                throw new \Exception('La reserva fue cancelada o venció. Vuelva a seleccionar el asiento.');
            }

            $caja = DB::table('cajas_sesiones')->where('usuario_id', $usuarioId)->where('estado', 'ABIERTA')->first();
            if (! $caja) {
                throw new \Exception('No tiene una caja abierta. Abra caja para confirmar cobros.');
            }

            DB::table('boletos')->where('id', $boletoId)->update([
                'estado' => 'vendido',
                'sesion_caja_id' => $caja->id,
                'sucursal_id' => $caja->sucursal_id ?? DB::raw('sucursal_id'),
                'metodo_pago' => $metodo,
                'referencia_pago' => $referencia !== null && $referencia !== '' ? mb_substr(trim($referencia), 0, 60) : null,
                'fecha_pago' => now(),
                'fecha_expiracion_reserva' => null,
            ]);

            DB::table('movimientos_caja')->insert([
                'sesion_id' => $caja->id,
                'tipo_movimiento' => 'INGRESO',
                'origen_modulo' => 'PASAJE',
                'referencia_id' => $boletoId,
                'monto' => $boleto->precio_final,
                'metodo_pago' => $metodo,
                'descripcion' => "Venta Boleto #{$boleto->codigo_boleto} Asiento: {$boleto->numero_asiento}".($metodo === 'QR' ? ' (QR'.($referencia ? " op. {$referencia}" : '').')' : ''),
                'fecha_creacion' => now(),
            ]);
        });

        return $this->obtenerDatosTicket($boletoId);
    }

    /**
     * Confirma un boleto pagado vía la pasarela Libélula (llamado desde el
     * callback público o desde la conciliación manual). A diferencia de
     * confirmarPagoBoleto(), acá no hay un cajero interactivo esperando: el
     * pago pudo confirmarse minutos u horas despues, cuando quien vendio el
     * boleto ya cerró su caja. En ese caso el pago SI quedo confirmado (no
     * se pierde ni se vuelve a cobrar), pero el ingreso no puede asentarse
     * en ninguna caja hasta que alguien la abra: aplicado_caja=false deja
     * eso trazable para conciliar despues.
     *
     * @return array{pago_confirmado: bool, aplicado_caja: bool, mensaje: string}
     */
    public function confirmarPagoLibelula(int $boletoId, string $referenciaTransaccion): array
    {
        $boleto = DB::table('boletos')->where('id', $boletoId)->first();
        if (! $boleto) {
            return ['pago_confirmado' => false, 'aplicado_caja' => false, 'mensaje' => 'Boleto no encontrado.'];
        }
        if ($boleto->estado === 'vendido') {
            return ['pago_confirmado' => true, 'aplicado_caja' => true, 'mensaje' => 'El boleto ya estaba confirmado.'];
        }

        try {
            $this->confirmarPagoBoleto($boletoId, (int) $boleto->usuario_vendedor_id, 'QR', $referenciaTransaccion);

            return ['pago_confirmado' => true, 'aplicado_caja' => true, 'mensaje' => 'Pago confirmado y aplicado a caja.'];
        } catch (\Exception $e) {
            return ['pago_confirmado' => false, 'aplicado_caja' => false, 'mensaje' => $e->getMessage()];
        }
    }

    /**
     * Sustituto activo del webhook de Libélula: en vez de esperar a que
     * Libélula avise (necesita una URL pública que un XAMPP local no tiene),
     * pregunta directamente si la deuda de este boleto ya se pagó y, si es
     * así, la confirma y aplica a caja en el momento. Pensado para llamarse
     * desde el polling que ya existe cada pocos segundos en el modal de
     * cobro y en la pantalla del pasajero — así el cajero se entera solo,
     * sin tener que apretar "Verificar pagos" a mano.
     *
     * El modal del cajero sondea cada 5s y la pantalla del pasajero cada 3s
     * (a veces las dos a la vez, para el mismo boleto): golpear la API de
     * Libélula a ese ritmo no tiene sentido. Se limita a como maximo una
     * consulta real cada 8 segundos por boleto; el resto de los sondeos en
     * el medio simplemente no verifican nada nuevo (el siguiente si lo hace).
     *
     * @return bool true si el pago se acaba de confirmar en esta llamada.
     */
    public function intentarConfirmarLibelula(int $boletoId): bool
    {
        $cobro = LibelulaCobro::where('boleto_id', $boletoId)->where('estado', 'pendiente')->first();
        if (! $cobro) {
            return false;
        }

        if (! \Illuminate\Support\Facades\Cache::add("libelula_check_boleto_{$boletoId}", true, 8)) {
            return false;
        }

        $resultado = $this->libelula->consultarPagos($cobro->fecha_creacion, now()->format('Y-m-d H:i:s'));
        if (! $resultado['ok']) {
            return false;
        }

        $pago = collect($resultado['pagos'])->firstWhere('identificador', $cobro->identificador_deuda);
        if (! $pago) {
            return false;
        }

        $confirmacion = $this->confirmarPagoLibelula($boletoId, $pago['id_transaccion'] ?? $cobro->identificador_deuda);

        $cobro->update([
            'estado' => $confirmacion['aplicado_caja'] ? 'pagado' : 'pagado_sin_aplicar',
            'fecha_pago' => now(),
        ]);

        return $confirmacion['pago_confirmado'];
    }

    /** Cancela un cobro QR pendiente y libera el asiento. */
    public function cancelarCobroQr(int $boletoId): bool
    {
        return (bool) Boleto::where('id', $boletoId)->where('estado', 'reservado')->where('metodo_pago', 'QR')->update(['estado' => 'cancelado']);
    }

    /** Libera asientos de cobros QR que no se confirmaron a tiempo. */
    public function liberarCobrosQrVencidos(?int $viajeId = null): bool
    {
        $q = Boleto::where('estado', 'reservado')->where('metodo_pago', 'QR')
            ->whereNotNull('fecha_expiracion_reserva')->where('fecha_expiracion_reserva', '<', now());
        if ($viajeId) {
            $q->where('viaje_id', $viajeId);
        }

        return (bool) $q->update(['estado' => 'cancelado']);
    }

    /** Estado de un boleto para la pantalla del pasajero (sin datos personales). */
    public function estadoCobroBoleto(int $boletoId): ?object
    {
        return DB::table('boletos as b')
            ->join('viajes as v', 'v.id', '=', 'b.viaje_id')
            ->join('rutas as r', 'r.id', '=', 'v.ruta_id')
            ->leftJoin('rutas_paradas as rp', 'rp.id', '=', 'b.parada_id')
            ->leftJoin('rutas_paradas as rps', 'rps.id', '=', 'b.parada_subida_id')
            ->where('b.id', $boletoId)
            ->select([
                'b.id', 'b.estado', 'b.metodo_pago', 'b.precio_final', 'b.numero_asiento', 'b.codigo_boleto', 'b.sucursal_id',
                DB::raw('GREATEST(TIMESTAMPDIFF(SECOND, NOW(), b.fecha_expiracion_reserva), 0) AS segundos_restantes'),
                DB::raw('COALESCE(rps.nombre_parada, r.origen) AS origen'), DB::raw('COALESCE(rp.nombre_parada, r.destino) AS destino'), 'v.fecha_salida',
            ])
            ->first();
    }

    public function cambiarEstadoBoleto(int $id, string $estado): bool
    {
        return (bool) Boleto::where('id', $id)->update(['estado' => $estado]);
    }

    public function obtenerConteoAsientos(int $viajeId): array
    {
        $total = (int) (DB::table('viajes')->leftJoin('tipos_buses', 'viajes.tipo_bus_id', '=', 'tipos_buses.id')
            ->where('viajes.id', $viajeId)->value('tipos_buses.capacidad') ?? 40);

        $filas = DB::table('boletos')->where('viaje_id', $viajeId)->select('estado', DB::raw('COUNT(*) as cantidad'))->groupBy('estado')->get();

        $vendidos = 0;
        $reservados = 0;
        foreach ($filas as $fila) {
            if ($fila->estado === 'vendido') {
                $vendidos = (int) $fila->cantidad;
            }
            if ($fila->estado === 'reservado') {
                $reservados = (int) $fila->cantidad;
            }
        }

        return ['libres' => max(0, $total - ($vendidos + $reservados)), 'reservados' => $reservados, 'vendidos' => $vendidos];
    }

    /** Choferes activos (personal con perfil Chofer) para programar viajes. */
    public function listarChoferes()
    {
        try {
            return Personal::where('perfil', 'Chofer')->where('estado', 1)->orderBy('apellidos')->orderBy('nombres')->select('id', 'nombres', 'apellidos')->get();
        } catch (\Exception $e) {
            Log::error('RutaService::listarChoferes: '.$e->getMessage());

            return collect();
        }
    }

    public function obtenerParadasPorRuta(int $rutaId)
    {
        return DB::table('rutas_paradas')->where('ruta_id', $rutaId)->where('estado', 1)->orderBy('orden_index')->get();
    }

    public function obtenerTiposEncomienda()
    {
        return DB::table('encomienda_tipos')->where('estado', 1)->orderBy('precio_extra')->get();
    }

    /** Todos los tipos (activos e inactivos), para la pantalla de administración del arancel. */
    public function obtenerTodosTiposEncomienda()
    {
        return DB::table('encomienda_tipos')->orderBy('estado', 'desc')->orderBy('precio_extra')->get();
    }

    public function guardarTipoEncomienda(array $datos): array
    {
        if ($datos['nombre'] === '') {
            return ['status' => false, 'message' => 'El nombre es obligatorio.'];
        }
        if ($datos['precio_extra'] < 0 || $datos['precio_por_kg_excedente'] < 0) {
            return ['status' => false, 'message' => 'Los precios no pueden ser negativos.'];
        }

        $fila = [
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'precio_extra' => $datos['precio_extra'],
            'peso_incluido_kg' => $datos['peso_incluido_kg'],
            'precio_por_kg_excedente' => $datos['precio_por_kg_excedente'],
        ];

        if (! empty($datos['id'])) {
            DB::table('encomienda_tipos')->where('id', $datos['id'])->update($fila);

            return ['status' => true, 'id' => $datos['id']];
        }

        $fila['estado'] = 1;
        $id = DB::table('encomienda_tipos')->insertGetId($fila);

        return ['status' => true, 'id' => $id];
    }

    public function cambiarEstadoTipoEncomienda(int $id): bool
    {
        $actual = DB::table('encomienda_tipos')->where('id', $id)->value('estado');
        if ($actual === null) {
            return false;
        }

        return (bool) DB::table('encomienda_tipos')->where('id', $id)->update(['estado' => $actual ? 0 : 1]);
    }

    /** Solo se puede borrar si ninguna encomienda lo usó (si no, se desactiva en vez de borrar). */
    public function eliminarTipoEncomienda(int $id): array
    {
        $enUso = DB::table('detalles_encomienda')->where('tipo_paquete_id', $id)->exists();
        if ($enUso) {
            return ['status' => false, 'message' => 'Este tipo ya se usó en encomiendas registradas: desactívelo en vez de eliminarlo.'];
        }

        DB::table('encomienda_tipos')->where('id', $id)->delete();

        return ['status' => true];
    }

    public function calcularPrecioDinamico(int $paradaId, string $tipo, ?int $tipoPaqueteId = null, float $peso = 0): array
    {
        $parada = DB::table('rutas_paradas')->where('id', $paradaId)->first();
        if (! $parada) {
            return ['status' => false, 'message' => 'Parada no encontrada'];
        }

        $precioFinal = 0.0;
        $detalles = [];

        if ($tipo === 'pasajero') {
            $precioFinal = (float) $parada->precio_pasaje;
            $detalles[] = 'Pasaje a '.$parada->nombre_parada;
        } elseif ($tipo === 'encomienda') {
            $precioBase = (float) $parada->precio_base_encomienda;
            $precioFinal += $precioBase;
            $detalles[] = 'Envío base a '.$parada->nombre_parada.' ('.number_format($precioBase, 2).' Bs)';

            if ($tipoPaqueteId) {
                $paquete = DB::table('encomienda_tipos')->where('id', $tipoPaqueteId)->first();
                if ($paquete) {
                    $extra = (float) $paquete->precio_extra;
                    $precioFinal += $extra;
                    $detalles[] = 'Tipo: '.$paquete->nombre.' (+'.number_format($extra, 2).' Bs)';

                    // El peso solo suma si pasa del incluido en el tipo de paquete
                    // (p. ej. "Caja pequeña" ya cubre hasta 5kg en precio_extra;
                    // lo que pese de mas se cobra por kg). Sin peso_incluido_kg
                    // cargado, el tipo no tiene tope de peso.
                    if ($paquete->peso_incluido_kg !== null && $peso > (float) $paquete->peso_incluido_kg) {
                        $excedente = $peso - (float) $paquete->peso_incluido_kg;
                        $cargoPeso = $excedente * (float) $paquete->precio_por_kg_excedente;
                        if ($cargoPeso > 0) {
                            $precioFinal += $cargoPeso;
                            $detalles[] = number_format($excedente, 2).'kg extra × '.number_format((float) $paquete->precio_por_kg_excedente, 2).' Bs/kg (+'.number_format($cargoPeso, 2).' Bs)';
                        }
                    }
                }
            }
        }

        return ['status' => true, 'precio_total' => $precioFinal, 'desglose' => implode(' + ', $detalles), 'moneda' => 'Bs'];
    }
}
