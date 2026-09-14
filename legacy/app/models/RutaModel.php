<?php

class RutaModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Listar todas las rutas ordenadas por ID descendente
     * @param int $limit Límite de registros
     * @param int $offset Desplazamiento
     * @return array Lista de rutas
     */
    public function listarRutas($limit = 100, $offset = 0)
    {
        $this->db->query("SELECT * FROM rutas ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    /**
     * Obtener asientos ocupados para un viaje específico
     * @param int $viajeId
     * @return array Array simple de números de asiento [1, 5, 20]
     */
    public function obtenerAsientosOcupados($viajeId, $subidaId = 0, $bajadaId = 0)
    {
        try {
            $this->liberarCobrosQrVencidos($viajeId);

            // Ocupacion por tramo: un asiento vendido El Alto -> Huarina queda libre
            // para Achacachi -> Copacabana. Sin tramo = ruta completa.
            require_once APPROOT . '/models/TramoModel.php';
            $tramos = new TramoModel();
            $this->db->query("SELECT ruta_id FROM viajes WHERE id = :id");
            $this->db->bind(':id', $viajeId);
            $rutaId = $this->db->single()->ruta_id ?? 0;

            $ordenSube = $tramos->orden($rutaId, $subidaId, false) ?? 0;
            $ordenBaja = $tramos->orden($rutaId, $bajadaId, true) ?? TramoModel::DESTINO;

            return $tramos->asientosOcupados($viajeId, $ordenSube, $ordenBaja);
        } catch (Exception $e) {
            error_log("Error obtenerAsientosOcupados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Listar rutas activas para selects
     */
    public function listarRutasActivas()
    {
        $this->db->query("SELECT * FROM rutas WHERE estado = 1 ORDER BY origen ASC, destino ASC");
        return $this->db->resultSet();
    }

    /**
     * Registrar una nueva ruta
     * VALIDACIÓN DE NEGOCIO: El origen y destino NO pueden ser iguales
     * @param string $origen Ciudad de origen
     * @param string $destino Ciudad de destino
     * @return bool|string True si se registró correctamente, mensaje de error si falló
     */
    public function registrarRuta($origen, $destino)
    {
        // Validación de negocio: origen y destino no pueden ser iguales
        if (strtoupper(trim($origen)) === strtoupper(trim($destino))) {
            return "El origen y el destino no pueden ser iguales";
        }

        // Validación: campos no vacíos
        if (empty(trim($origen)) || empty(trim($destino))) {
            return "El origen y el destino son obligatorios";
        }

        try {
            $this->db->query("INSERT INTO rutas (origen, destino, estado, fecha_creacion) VALUES (:origen, :destino, 1, NOW())");
            $this->db->bind(':origen', $origen);
            $this->db->bind(':destino', $destino);

            if ($this->db->execute()) {
                return true;
            } else {
                return "Error al registrar la ruta";
            }
        } catch (PDOException $e) {
            return "Error de base de datos: " . $e->getMessage();
        }
    }

    /**
     * Obtener ruta por ID
     */
    public function obtenerRutaPorId($id)
    {
        $this->db->query("SELECT * FROM rutas WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Actualizar ruta existente
     * VALIDACIÓN DE NEGOCIO: El origen y destino NO pueden ser iguales
     */
    public function actualizarRuta($id, $origen, $destino, $estado = 1)
    {
        // Validación de negocio: origen y destino no pueden ser iguales
        if (strtoupper(trim($origen)) === strtoupper(trim($destino))) {
            return "El origen y el destino no pueden ser iguales";
        }

        try {
            $this->db->query("UPDATE rutas SET origen = :origen, destino = :destino, estado = :estado WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->bind(':origen', $origen);
            $this->db->bind(':destino', $destino);
            $this->db->bind(':estado', $estado);

            if ($this->db->execute()) {
                return true;
            } else {
                return "Error al actualizar la ruta";
            }
        } catch (PDOException $e) {
            return "Error de base de datos: " . $e->getMessage();
        }
    }

    /**
     * Eliminar ruta (Definición de ruta)
     * Verifica que no tenga viajes asociados antes de eliminar
     */
    public function eliminarRuta($id)
    {
        try {
            // Verificar si la ruta tiene viajes asociados
            $this->db->query("SELECT COUNT(*) as total FROM viajes WHERE ruta_id = :id");
            $this->db->bind(':id', $id);
            $result = $this->db->single();

            if ($result->total > 0) {
                return "No se puede eliminar la ruta porque tiene {$result->total} viaje(s) asociado(s). Desactívela en su lugar.";
            }

            // Si no tiene viajes asociados, proceder con la eliminación
            $this->db->query("DELETE FROM rutas WHERE id = :id");
            $this->db->bind(':id', $id);

            if ($this->db->execute()) {
                return true;
            } else {
                return "Error al eliminar la ruta";
            }
        } catch (PDOException $e) {
            return "Error de base de datos: " . $e->getMessage();
        }
    }

    /**
     * CAMBIO: Eliminar un viaje programado específico
     */
    public function eliminarViaje($id)
    {
        try {
            error_log("Intentando eliminar viaje con ID: " . $id);

            // Verificar que el viaje existe
            $this->db->query("SELECT id, estado FROM viajes WHERE id = :id");
            $this->db->bind(':id', $id);
            $viaje = $this->db->single();

            if (!$viaje) {
                return "El viaje no existe";
            }

            // ✅ PROTECCIÓN: No permitir eliminar viajes finalizados (proteger historial)
            if ($viaje->estado === 'Finalizado') {
                return "🚫 No se puede eliminar un viaje FINALIZADO. Los viajes finalizados son parte del historial contable y deben permanecer en el sistema para auditoría.";
            }

            // ✅ VALIDACIÓN: Verificar si hay boletos vendidos/reservados
            $this->db->query("SELECT COUNT(*) as total FROM boletos WHERE viaje_id = :id");
            $this->db->bind(':id', $id);
            $resBoletos = $this->db->single();

            if ($resBoletos->total > 0) {
                return "🚫 No se puede eliminar: Este viaje tiene " . $resBoletos->total . " boletos vendidos o reservados. Use el botón 'Despachar' para finalizar el viaje y crear uno nuevo.";
            }

            // Eliminar el viaje (solo si está Programado/Activo y sin boletos)
            $this->db->query("DELETE FROM viajes WHERE id = :id");
            $this->db->bind(':id', $id);

            if ($this->db->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error PDO al eliminar viaje ID {$id}: " . $e->getMessage());
            // Mensaje amigable si es restricción de llave foránea
            if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                return "No se puede eliminar debido a que tiene registros relacionados (Boletos, Encomiendas, etc).";
            }
            return "Error de base de datos: " . $e->getMessage();
        }
    }

    /**
     * Cambiar estado de ruta (Activo/Inactivo)
     */
    public function cambiarEstado($id, $estado)
    {
        $this->db->query("UPDATE rutas SET estado = :estado WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':estado', $estado);
        return $this->db->execute();
    }

    /**
     * Contar total de rutas
     */
    public function contarRutas()
    {
        $this->db->query("SELECT COUNT(*) as total FROM rutas");
        $result = $this->db->single();
        return $result->total;
    }

    /**
     * Buscar rutas por término
     */
    public function buscarRutas($termino, $limit = 10, $offset = 0)
    {
        $this->db->query("SELECT * FROM rutas WHERE origen LIKE :termino OR destino LIKE :termino ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $this->db->bind(':termino', '%' . $termino . '%');
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    /**
     * Guardar nueva ruta de viaje (viaje programado)
     * @param array $data Datos del viaje
     * @return bool|string True si se guardó correctamente, mensaje de error si falló
     */
    public function guardarRutaViaje($data)
    {
        try {
            // Validar campos obligatorios
            if (empty($data['ruta_id']) || empty($data['tipo_bus_id']) || empty($data['fecha_salida'])) {
                return "Faltan campos obligatorios";
            }

            // Validar que terminal origen y destino sean diferentes
            if (
                !empty($data['terminal_origen_id']) && !empty($data['terminal_destino_id'])
                && $data['terminal_origen_id'] == $data['terminal_destino_id']
            ) {
                return "La terminal de origen y destino deben ser diferentes";
            }

            // Preparar servicios incluidos como JSON
            $serviciosIncluidos = null;
            if (isset($data['servicios']) && is_array($data['servicios'])) {
                $serviciosIncluidos = json_encode($data['servicios']);
            }

            // Combinar fecha y hora de salida
            // CORRECCIÓN: Asegurar formato Y-m-d para MySQL (evita error con d/m/Y)
            $fechaSegura = date('Y-m-d', strtotime(str_replace('/', '-', $data['fecha_salida'])));
            $fechaHoraSalida = $fechaSegura;

            if (!empty($data['hora_salida'])) {
                $fechaHoraSalida .= ' ' . $data['hora_salida'];
            } else {
                $fechaHoraSalida .= ' 00:00:00';
            }

            // Calcular fecha y hora de llegada estimada
            $fechaHoraLlegada = null;
            if (!empty($data['hora_llegada'])) {
                $fechaHoraLlegada = $fechaSegura . ' ' . $data['hora_llegada'];

                // Si la hora de llegada es menor que la de salida, es al día siguiente
                if (strtotime($data['hora_llegada']) < strtotime($data['hora_salida'])) {
                    $fechaHoraLlegada = date('Y-m-d H:i:s', strtotime($fechaSegura . ' +1 day ' . $data['hora_llegada']));
                }
            }

            // Validar bus_id y chofer_id (permitir nulos si no se seleccionaron)
            $busId = !empty($data['bus_id']) ? $data['bus_id'] : null;
            $choferId = !empty($data['chofer_id']) ? $data['chofer_id'] : null;

            if ($busId) {
                // El mapa de asientos debe ser el del bus real: el tipo del viaje sale del bus.
                $this->db->query("SELECT v.placa, v.estado, v.tipo_bus_id, tb.capacidad
                                  FROM vehiculos v LEFT JOIN tipos_buses tb ON tb.id = v.tipo_bus_id
                                  WHERE v.id = :id");
                $this->db->bind(':id', $busId);
                $bus = $this->db->single();
                if (!$bus || $bus->estado != 1) {
                    return "El bus seleccionado no existe o está dado de baja.";
                }
                if (!$bus->tipo_bus_id) {
                    return "El bus {$bus->placa} no tiene tipo de bus (asientos) definido. Complételo en Gestión de Flota.";
                }
                $data['tipo_bus_id'] = $bus->tipo_bus_id;

                // Chofer por defecto: el asignado al bus
                if (!$choferId) {
                    $this->db->query("SELECT chofer_id FROM asignaciones_buses WHERE bus_id = :id AND estado = 1 LIMIT 1");
                    $this->db->bind(':id', $busId);
                    $asig = $this->db->single();
                    $choferId = $asig->chofer_id ?? null;
                }
            }

            if (!empty($data['id'])) {
                // No achicar el bus por debajo de un asiento ya vendido/reservado
                $this->db->query("SELECT tb.capacidad,
                                         (SELECT MAX(b.numero_asiento) FROM boletos b WHERE b.viaje_id = :vid AND b.estado IN ('vendido','reservado')) AS max_asiento
                                  FROM tipos_buses tb WHERE tb.id = :tipo");
                $this->db->bind(':vid', $data['id']);
                $this->db->bind(':tipo', $data['tipo_bus_id']);
                $cap = $this->db->single();
                if ($cap && (int) $cap->max_asiento > (int) $cap->capacidad) {
                    return "No se puede cambiar a un bus de {$cap->capacidad} asientos: el viaje ya tiene vendido o reservado el asiento {$cap->max_asiento}.";
                }
            }

            if ($choferId) {
                $this->db->query("SELECT nombres, apellidos, perfil, estado FROM personal WHERE id = :id");
                $this->db->bind(':id', $choferId);
                $chofer = $this->db->single();
                if (!$chofer || $chofer->estado != 1 || $chofer->perfil !== 'Chofer') {
                    return "El conductor seleccionado no es un chofer activo del personal.";
                }

                // Un chofer no puede salir en dos viajes a la misma hora
                $this->db->query("SELECT v.id, r.origen, r.destino FROM viajes v JOIN rutas r ON r.id = v.ruta_id
                                  WHERE v.chofer_id = :chofer AND v.fecha_salida = :salida AND v.id <> :id
                                    AND LOWER(v.estado) NOT IN ('finalizado', 'cancelado', 'inactivo')
                                  LIMIT 1");
                $this->db->bind(':chofer', $choferId);
                $this->db->bind(':salida', $fechaHoraSalida);
                $this->db->bind(':id', (int) ($data['id'] ?? 0));
                if ($choque = $this->db->single()) {
                    return "{$chofer->nombres} {$chofer->apellidos} ya sale a esa hora en el viaje #{$choque->id} ({$choque->origen} - {$choque->destino}).";
                }
            }

            // Determinar si es actualización o inserción
            if (!empty($data['id'])) {
                // Actualizar viaje existente
                $sql = "UPDATE viajes SET 
                        ruta_id = :ruta_id,
                        tipo_bus_id = :tipo_bus_id,
                        terminal_origen_id = :terminal_origen_id,
                        terminal_destino_id = :terminal_destino_id,
                        bus_id = :bus_id,
                        chofer_id = :chofer_id,
                        fecha_salida = :fecha_salida,
                        hora_salida = :hora_salida,
                        fecha_llegada_estimada = :fecha_llegada_estimada,
                        hora_llegada = :hora_llegada,
                        precio_base = :precio_base,
                        tipo_servicio = :tipo_servicio,
                        servicios_incluidos = :servicios_incluidos,
                        notas = :notas,
                        estado = :estado,
                        fecha_actualizacion = NOW()
                        WHERE id = :id";

                $this->db->query($sql);
                $this->db->bind(':id', $data['id']);
            } else {
                // Insertar nuevo viaje
                $sql = "INSERT INTO viajes (
                        ruta_id, tipo_bus_id, terminal_origen_id, terminal_destino_id,
                        bus_id, chofer_id,
                        fecha_salida, hora_salida, fecha_llegada_estimada, hora_llegada,
                        precio_base, tipo_servicio, servicios_incluidos, notas, estado,
                        fecha_creacion
                    ) VALUES (
                        :ruta_id, :tipo_bus_id, :terminal_origen_id, :terminal_destino_id,
                        :bus_id, :chofer_id,
                        :fecha_salida, :hora_salida, :fecha_llegada_estimada, :hora_llegada,
                        :precio_base, :tipo_servicio, :servicios_incluidos, :notas, :estado,
                        NOW()
                    )";

                $this->db->query($sql);
            }

            // Bind de parámetros
            $this->db->bind(':ruta_id', $data['ruta_id']);
            $this->db->bind(':tipo_bus_id', $data['tipo_bus_id']);
            $this->db->bind(':terminal_origen_id', $data['terminal_origen_id'] ?? null);
            $this->db->bind(':terminal_destino_id', $data['terminal_destino_id'] ?? null);
            $this->db->bind(':bus_id', $busId);
            $this->db->bind(':chofer_id', $choferId);
            $this->db->bind(':fecha_salida', $fechaHoraSalida);
            $this->db->bind(':hora_salida', $data['hora_salida'] ?? null);
            $this->db->bind(':fecha_llegada_estimada', $fechaHoraLlegada);
            $this->db->bind(':hora_llegada', $data['hora_llegada'] ?? null);
            $this->db->bind(':precio_base', $data['precio_base'] ?? 0);
            $this->db->bind(':tipo_servicio', $data['tipo_servicio'] ?? 'Ejecutivo');
            $this->db->bind(':servicios_incluidos', $serviciosIncluidos);
            $this->db->bind(':notas', $data['notas']);
            $this->db->bind(':estado', $data['estado'] ?? 'Programado');

            if ($this->db->execute()) {
                // El chofer del viaje NO cambia la asignacion permanente del bus
                // (antes se sobrescribia en silencio). Eso se gestiona en Asignar Buses.
                return true;
            } else {
                return "Error al guardar la ruta de viaje";
            }
        } catch (PDOException $e) {
            return "Error de base de datos: " . $e->getMessage();
        }
    }

    /**
     * Buses activos de un tipo, con su tripulacion por defecto.
     * (Antes el filtro por tipo estaba deshabilitado porque vehiculos no tenia
     * tipo_bus_id, y se ofrecian buses de cualquier capacidad.)
     */
    public function obtenerBusesPorTipo($tipoBusId)
    {
        $this->db->query("SELECT v.id, v.placa, v.marca, v.modelo, v.asientos, v.tipo_servicio, v.id AS numero_interno,
                                 CONCAT(c.nombres, ' ', c.apellidos) AS chofer_asignado
                          FROM vehiculos v
                          LEFT JOIN asignaciones_buses ab ON ab.bus_id = v.id AND ab.estado = 1
                          LEFT JOIN personal c ON c.id = ab.chofer_id
                          WHERE v.estado = 1 AND v.tipo_bus_id = :tipo
                          ORDER BY v.placa");
        $this->db->bind(':tipo', $tipoBusId);
        return $this->db->resultSet();
    }

    /**
     * Datos del bus y su tripulacion por defecto (asignaciones_buses -> personal).
     * Antes tomaba los nombres de `usuarios` (ids de otra tabla) e intentaba
     * crear asignaciones con chofer NULL cuando el bus no tenia una.
     */
    public function obtenerTripulacionBus($busId)
    {
        try {
            $this->db->query("
                SELECT
                    v.id AS bus_id,
                    ab.chofer_id,
                    CONCAT(c.nombres, ' ', c.apellidos) AS nombre_chofer,
                    ab.copiloto_id,
                    CONCAT(cp.nombres, ' ', cp.apellidos) AS nombre_copiloto,
                    v.placa AS bus_placa,
                    v.id AS bus_numero,
                    v.marca AS bus_marca,
                    v.modelo AS bus_modelo,
                    COALESCE(tb.nombre, v.tipo_servicio) AS tipo_bus,
                    COALESCE(tb.pisos, 1) AS bus_pisos,
                    COALESCE(tb.capacidad, v.asientos) AS bus_capacidad
                FROM vehiculos v
                LEFT JOIN tipos_buses tb ON tb.id = v.tipo_bus_id
                LEFT JOIN asignaciones_buses ab ON ab.bus_id = v.id AND ab.estado = 1
                LEFT JOIN personal c ON c.id = ab.chofer_id
                LEFT JOIN personal cp ON cp.id = ab.copiloto_id
                WHERE v.id = :bus_id
                LIMIT 1
            ");
            $this->db->bind(':bus_id', $busId);
            return $this->db->single() ?: false;
        } catch (PDOException $e) {
            error_log('RutaModel::obtenerTripulacionBus: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar viajes programados con información completa
     * @param int $limit Límite de registros
     * @param int $offset Desplazamiento
     * @return array Lista de viajes programados
     */
    public function listarViajesProgramados($limit = 100, $offset = 0)
    {
        try {
            // Se filtran los viajes que NO estén finalizados ni cancelados para la vista principal
            $sql = "SELECT 
                    v.id,
                    v.fecha_salida,
                    v.hora_salida,
                    v.hora_llegada,
                    v.precio_base,
                    v.tipo_servicio,
                    v.estado,
                    r.origen,
                    r.destino,
                    tb.nombre as tipo_bus,
                    tb.capacidad,
                    to_term.nombre_sede as terminal_origen,
                    td_term.nombre_sede as terminal_destino,
                    CONCAT(u1.nombres, ' ', u1.apellidos) as chofer_nombre, -- u1 = personal (viajes.chofer_id -> personal)
                    b.placa as bus_placa,
                    b.id as bus_numero
                FROM viajes v
                INNER JOIN rutas r ON v.ruta_id = r.id
                LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
                LEFT JOIN terminales to_term ON v.terminal_origen_id = to_term.id
                LEFT JOIN terminales td_term ON v.terminal_destino_id = td_term.id
                LEFT JOIN personal u1 ON v.chofer_id = u1.id
                LEFT JOIN vehiculos b ON v.bus_id = b.id
                WHERE v.estado NOT IN ('Finalizado', 'Cancelado')
                ORDER BY v.fecha_salida ASC, v.hora_salida ASC
                LIMIT :limit OFFSET :offset";

            $this->db->query($sql);
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);
            return $this->db->resultSet();
        } catch (PDOException $e) {
            error_log("Error en listarViajesProgramados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Despachar bus: Finalizar viaje actual y crear uno nuevo (clon) para reutilizar el bus
     * Preserva integridad contable - NO borra boletos vendidos
     */
    public function despacharYCrearNuevo($viajeId)
    {
        try {
            $this->db->beginTransaction();

            // 1. Obtener datos del viaje actual
            $viajeActual = $this->obtenerViajePorId($viajeId);
            if (!$viajeActual) {
                throw new Exception("Viaje no encontrado");
            }

            // 2. Finalizar el viaje actual (archivar - NO borrar)
            $this->db->query("UPDATE viajes SET estado = 'Finalizado', fecha_actualizacion = NOW() WHERE id = :id");
            $this->db->bind(':id', $viajeId);
            $this->db->execute();

            // 3. Crear nuevo viaje (clon limpio para reutilizar el bus)
            $sql = "INSERT INTO viajes (
                        ruta_id, tipo_bus_id, bus_id, chofer_id,
                        terminal_origen_id, terminal_destino_id,
                        fecha_salida, hora_salida, hora_llegada,
                        precio_base, tipo_servicio, servicios_incluidos,
                        notas, estado, fecha_creacion
                    ) VALUES (
                        :ruta_id, :tipo_bus_id, :bus_id, :chofer_id,
                        :terminal_origen_id, :terminal_destino_id,
                        :fecha_salida, :hora_salida, :hora_llegada,
                        :precio_base, :tipo_servicio, :servicios_incluidos,
                        :notas, 'Programado', NOW()
                    )";

            $this->db->query($sql);

            // Copiar configuración del viaje anterior
            $this->db->bind(':ruta_id', $viajeActual->ruta_id);
            $this->db->bind(':tipo_bus_id', $viajeActual->tipo_bus_id);
            $this->db->bind(':bus_id', $viajeActual->bus_id);
            $this->db->bind(':chofer_id', $viajeActual->chofer_id);
            $this->db->bind(':terminal_origen_id', $viajeActual->terminal_origen_id);
            $this->db->bind(':terminal_destino_id', $viajeActual->terminal_destino_id);

            // Nueva fecha: Siguiente día a la misma hora (personalizable)
            $nuevaFecha = date('Y-m-d', strtotime($viajeActual->fecha_salida . ' +1 day'));
            $this->db->bind(':fecha_salida', $nuevaFecha);
            $this->db->bind(':hora_salida', $viajeActual->hora_salida);
            $this->db->bind(':hora_llegada', $viajeActual->hora_llegada);
            $this->db->bind(':precio_base', $viajeActual->precio_base);
            $this->db->bind(':tipo_servicio', $viajeActual->tipo_servicio);
            $this->db->bind(':servicios_incluidos', $viajeActual->servicios_incluidos);
            $this->db->bind(':notas', 'Viaje generado automáticamente desde despacho');

            $this->db->execute();
            $nuevoViajeId = $this->db->lastInsertId();

            $this->db->commit();

            error_log("✅ Viaje $viajeId finalizado. Nuevo viaje creado: $nuevoViajeId");
            return $nuevoViajeId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("❌ Error en despacharYCrearNuevo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar estado de un viaje (uso general)
     */
    public function cambiarEstadoViaje($id, $nuevoEstado)
    {
        try {
            $this->db->query("UPDATE viajes SET estado = :estado WHERE id = :id");
            $this->db->bind(':estado', $nuevoEstado);
            $this->db->bind(':id', $id);
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }


    public function obtenerViajePorId($id)
    {
        $sql = "SELECT 
                v.*,
                r.origen,
                r.destino,
                tb.nombre as tipo_bus,
                COALESCE(tb.capacidad, 0) as capacidad,
                COALESCE(term_origen.nombre_sede, 'Sin Asignar') as terminal_origen,
                COALESCE(term_destino.nombre_sede, 'Sin Asignar') as terminal_destino,
                COALESCE(CONCAT(p_viaje.nombres, ' ', p_viaje.apellidos), CONCAT(p_chofer.nombres, ' ', p_chofer.apellidos), 'PENDIENTE DE ASIGNACIÓN') as chofer_nombre,
                COALESCE(CONCAT(p_copiloto.nombres, ' ', p_copiloto.apellidos), 'PENDIENTE DE ASIGNACIÓN') as copiloto_nombre,
                COALESCE(b.placa, 'PENDIENTE DE ASIGNACIÓN') as bus_placa,
                COALESCE(b.id, 'S/N') as bus_numero
            FROM viajes v
            INNER JOIN rutas r ON v.ruta_id = r.id
            LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
            LEFT JOIN terminales term_origen ON v.terminal_origen_id = term_origen.id
            LEFT JOIN terminales term_destino ON v.terminal_destino_id = term_destino.id
            LEFT JOIN vehiculos b ON v.bus_id = b.id
            LEFT JOIN personal p_viaje ON v.chofer_id = p_viaje.id
            LEFT JOIN asignaciones_buses ab ON b.id = ab.bus_id AND ab.estado = 1
            LEFT JOIN personal p_copiloto ON ab.copiloto_id = p_copiloto.id
            LEFT JOIN personal p_chofer ON ab.chofer_id = p_chofer.id
            WHERE v.id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }





    /**
     * Buscar o crear cliente (Helper)
     */
    private function buscarOCrearCliente($nombreCompleto, $numeroDocumento)
    {
        try {
            $this->db->query("SELECT id FROM clientes WHERE numero_documento = :documento LIMIT 1");
            $this->db->bind(':documento', $numeroDocumento);
            $cliente = $this->db->single();

            if ($cliente) return $cliente->id;

            $partes = explode(' ', trim($nombreCompleto), 2);
            $nombres = $partes[0] ?? $nombreCompleto;
            $apellidos = $partes[1] ?? '';

            $sql = "INSERT INTO clientes (tipo_documento, numero_documento, nombres, apellidos) VALUES ('CI', :documento, :nombres, :apellidos)";
            $this->db->query($sql);
            $this->db->bind(':documento', $numeroDocumento);
            $this->db->bind(':nombres', $nombres);
            $this->db->bind(':apellidos', $apellidos);

            if ($this->db->execute()) return $this->db->lastInsertId();
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * TRANSACCIÓN COMPLETA: Registrar Venta/Reserva (Nuevo Boleto)
     * Verifica Cliente -> Inserta Cliente (si no existe) -> Inserta Boleto
     */
    public function registrarVentaTransaccion($datos)
    {
        try {
            $this->db->beginTransaction();

            // 1. Gestionar Cliente
            $this->db->query("SELECT id, celular FROM clientes WHERE numero_documento = :doc");
            $this->db->bind(':doc', $datos['documento']);
            $cliente = $this->db->single();

            $clienteId = 0;
            if ($cliente) {
                $clienteId = $cliente->id;

                // ✅ LÓGICA INTELIGENTE: Solo actualizar celular si se proporciona uno nuevo
                $celularActualizar = $datos['celular'];

                // Si el usuario no proporcionó celular, mantener el existente
                if (empty($celularActualizar) && !empty($cliente->celular)) {
                    $celularActualizar = $cliente->celular;
                }

                // Actualizar datos del cliente
                $this->db->query("UPDATE clientes SET nombres = :n, apellidos = :a, celular = :c WHERE id = :id");
                $this->db->bind(':n', $datos['nombres']);
                $this->db->bind(':a', $datos['apellidos']);
                $this->db->bind(':c', $celularActualizar);
                $this->db->bind(':id', $clienteId);
                $this->db->execute();
            } else {
                // Cliente nuevo: insertar con todos los datos
                $this->db->query("INSERT INTO clientes (tipo_documento, numero_documento, nombres, apellidos, celular) VALUES ('CI', :doc, :n, :a, :c)");
                $this->db->bind(':doc', $datos['documento']);
                $this->db->bind(':n', $datos['nombres']);
                $this->db->bind(':a', $datos['apellidos']);
                $this->db->bind(':c', $datos['celular']);
                $this->db->execute();
                $clienteId = $this->db->lastInsertId();
            }

            // 1. Obtener caja abierta (y su sucursal)
            $this->db->query("SELECT id, sucursal_id FROM cajas_sesiones WHERE usuario_id = :uid AND estado = 'ABIERTA'");
            $this->db->bind(':uid', $datos['usuario_id']);
            $cajaAbierta = $this->db->single();

            // Sucursal de la operacion: la de la caja abierta; si es una reserva sin caja, la del usuario
            $sucursalVenta = $cajaAbierta->sucursal_id ?? null;
            if (!$sucursalVenta) {
                $this->db->query("SELECT sucursal_id FROM usuarios WHERE id = :uid");
                $this->db->bind(':uid', $datos['usuario_id']);
                $sucursalVenta = $this->db->single()->sucursal_id ?? null;
            }

            // Venta, o cobro QR (se confirmara en esta caja): requiere caja abierta
            if ($datos['estado'] == 'vendido' || ($datos['metodo_pago'] ?? '') === 'QR') {
                if (!$cajaAbierta) {
                    throw new Exception("No tienes una Caja Abierta. Debes abrir caja para realizar ventas.");
                }
                $sesionId = $datos['estado'] == 'vendido' ? $cajaAbierta->id : null;
            } else {
                $sesionId = null; // Reservas no mueven dinero aún
            }

            // 2a. El asiento debe existir en el bus del viaje (antes se podia vender
            //     cualquier numero, incluso mayor que la capacidad).
            $this->db->query("SELECT COALESCE(tb.capacidad, 0) AS capacidad FROM viajes v LEFT JOIN tipos_buses tb ON tb.id = v.tipo_bus_id WHERE v.id = :vid");
            $this->db->bind(':vid', $datos['viaje_id']);
            $capacidad = (int) ($this->db->single()->capacidad ?? 0);
            $asiento = (int) $datos['asiento'];
            if ($capacidad <= 0) {
                throw new Exception("El viaje #{$datos['viaje_id']} no tiene un bus con asientos definidos.");
            }
            if ($asiento < 1 || $asiento > $capacidad) {
                throw new Exception("El asiento {$datos['asiento']} no existe en este bus (asientos 1 a {$capacidad}).");
            }

            // 2. Verificar asiento disponible EN EL TRAMO.
            //    Se bloquea la fila del viaje: las ventas del mismo viaje se atienden de a una
            //    (antes dos ventas simultaneas del mismo asiento libre podian pasar ambas).
            $this->liberarCobrosQrVencidos($datos['viaje_id']);
            $this->db->query("SELECT id, ruta_id, precio_base FROM viajes WHERE id = :vid FOR UPDATE");
            $this->db->bind(':vid', $datos['viaje_id']);
            $viajeVenta = $this->db->single();

            $subidaId = (int) ($datos['parada_subida_id'] ?? 0);
            $bajadaId = (int) ($datos['parada_id'] ?? 0);
            require_once APPROOT . '/models/TramoModel.php';
            $tramos = new TramoModel();
            $ordenSube = $tramos->orden($viajeVenta->ruta_id, $subidaId, false);
            $ordenBaja = $tramos->orden($viajeVenta->ruta_id, $bajadaId, true);
            if ($ordenSube === null || $ordenBaja === null) {
                throw new Exception("La parada de subida o de bajada no pertenece a la ruta de este viaje.");
            }
            if ($ordenSube >= $ordenBaja) {
                throw new Exception("La parada de bajada debe estar después de la de subida.");
            }

            $this->db->query("SELECT b.id,
                                     COALESCE(ps.orden_index, 0) AS orden_sube,
                                     COALESCE(pb.orden_index, " . TramoModel::DESTINO . ") AS orden_baja
                              FROM boletos b
                              LEFT JOIN rutas_paradas ps ON ps.id = b.parada_subida_id
                              LEFT JOIN rutas_paradas pb ON pb.id = b.parada_id
                              WHERE b.viaje_id = :vid AND b.numero_asiento = :asiento AND b.estado IN ('vendido','reservado')
                              FOR UPDATE");
            $this->db->bind(':vid', $datos['viaje_id']);
            $this->db->bind(':asiento', $datos['asiento']);
            foreach ($this->db->resultSet() as $otro) {
                if ((int) $otro->orden_sube < $ordenBaja && $ordenSube < (int) $otro->orden_baja) {
                    throw new Exception("El asiento " . $datos['asiento'] . " ya está ocupado en ese tramo del viaje #" . $datos['viaje_id'] . ". Seleccione otro.");
                }
            }

            // Precio del tramo segun tarifa (el navegador no decide el precio)
            $tarifa = $tramos->tarifa($viajeVenta->ruta_id, $subidaId, $bajadaId);
            if ($tarifa !== null) {
                $datos['precio'] = $tarifa;
            } elseif ($subidaId === 0 && $bajadaId === 0) {
                $datos['precio'] = $viajeVenta->precio_base;
            }
            if ((float) $datos['precio'] <= 0) {
                throw new Exception("El tramo elegido no tiene tarifa. Configúrela en Rutas y tarifas.");
            }

            // 3. Código: correlativo de la sucursal (EAL-000123). El aleatorio anterior
            //    podia repetirse y hacer fallar la venta por el indice unico.
            $codigo = $sucursalVenta ? Sucursal::siguienteCodigo($this->db, $sucursalVenta) : null;
            $codigo = $codigo ?: $this->generarCodigoBoleto();

            // 4. Insertar Boleto
            $paradaId = $bajadaId ?: null;

            $metodoPago = ($datos['metodo_pago'] ?? 'EFECTIVO') === 'QR' ? 'QR' : 'EFECTIVO';
            // Cobro QR pendiente: el asiento queda reservado solo unos minutos
            $minutosQr = !empty($datos['minutos_reserva']) ? max(1, (int) $datos['minutos_reserva']) : null;

            $sql = "INSERT INTO boletos (viaje_id, cliente_id, usuario_vendedor_id, sesion_caja_id, sucursal_id, parada_subida_id, numero_asiento, precio_final, metodo_pago, fecha_pago, estado, fecha_reserva, fecha_expiracion_reserva, codigo_boleto, parada_id)
                    VALUES (:vid, :cid, :uid, :sesion, :sucursal, :subida, :asiento, :precio, :metodo, "  . ($datos['estado'] == 'vendido' ? 'NOW()' : 'NULL') . ", :estado, NOW(), " . ($minutosQr ? 'DATE_ADD(NOW(), INTERVAL ' . $minutosQr . ' MINUTE)' : 'NULL') . ", :codigo, :parada_id)";

            $this->db->query($sql);
            $this->db->bind(':vid', $datos['viaje_id']);
            $this->db->bind(':cid', $clienteId);
            $this->db->bind(':uid', $datos['usuario_id']);
            $this->db->bind(':sesion', $sesionId);
            $this->db->bind(':sucursal', $sucursalVenta);
            $this->db->bind(':subida', $subidaId ?: null);
            $this->db->bind(':asiento', $datos['asiento']);
            $this->db->bind(':precio', $datos['precio']);
            $this->db->bind(':metodo', $metodoPago);
            $this->db->bind(':estado', $datos['estado']);
            $this->db->bind(':codigo', $codigo);
            $this->db->bind(':parada_id', $paradaId);

            $this->db->execute();
            $boletoId = $this->db->lastInsertId();

            // 5. REGISTRAR MOVIMIENTO DE CAJA (Solo si es venta)
            if ($datos['estado'] == 'vendido' && $sesionId) {
                // Insertar movimiento
                $sqlCaja = "INSERT INTO movimientos_caja (sesion_id, tipo_movimiento, origen_modulo, referencia_id, monto, metodo_pago, descripcion)
                            VALUES (:sesion, 'INGRESO', 'PASAJE', :ref, :monto, :metodo, :desc)";

                $descripcion = "Venta Boleto #" . $codigo . " Asiento: " . $datos['asiento'];

                $this->db->query($sqlCaja);
                $this->db->bind(':sesion', $sesionId);
                $this->db->bind(':ref', $boletoId);
                $this->db->bind(':monto', $datos['precio']);
                $this->db->bind(':metodo', $metodoPago);
                $this->db->bind(':desc', $descripcion);
                $this->db->execute();
            }

            $this->db->commit();

            // Retornar datos completos del ticket
            return $this->obtenerDatosTicket($boletoId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Generar código de boleto único
     * @return string
     */
    private function generarCodigoBoleto()
    {
        // Formato: BOL-YYYYMMDD-XXXXX
        $fecha = date('Ymd');
        $random = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return "BOL-{$fecha}-{$random}";
    }

    /**
     * Obtener sesión de caja activa
     * @param int $usuarioId
     * @return int
     */
    private function obtenerSesionCajaActiva($usuarioId)
    {
        try {
            $this->db->query("SELECT id FROM sesiones_caja WHERE usuario_id = :usuario_id AND estado = 'abierta' ORDER BY id DESC LIMIT 1");
            $this->db->bind(':usuario_id', $usuarioId);
            $sesion = $this->db->single();

            if ($sesion) {
                return $sesion->id;
            }

            // Si no hay sesión activa, retornar 1 (sesión por defecto)
            return 1;
        } catch (Exception $e) {
            error_log("Error obtenerSesionCajaActiva: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Obtener datos completos para el ticket (INNER JOINs Críticos)
     * Resuelve el requerimiento de nombres reales de ciudades.
     */
    public function obtenerDatosTicket($boletoId)
    {
        try {
            $sql = "SELECT 
                    b.id as id_boleto,
                    b.codigo_boleto,
                    b.numero_asiento,
                    b.precio_final as precio,
                    b.fecha_reserva as fecha_venta,
                    
                    -- Datos del cliente
                    CONCAT(c.nombres, ' ', c.apellidos) as nombre_pasajero,
                    c.numero_documento,
                    
                    v.fecha_salida,
                    v.hora_salida,
                    
                    -- Nombres reales de Ciudades (Ruta)
                COALESCE(rps.nombre_parada, r.origen) as ciudad_origen,
                COALESCE(rp.nombre_parada, r.destino) as ciudad_destino,
                
                -- Nombres de Terminales (Opcional, pero útil)
                ter_orig.nombre_sede as terminal_origen,
                
                -- Datos del Bus
                bus.placa as bus_placa,
                bus.id as bus_numero,
                suc.nombre_sede as sucursal_nombre,
                suc.direccion as sucursal_direccion,
                suc.telefono as sucursal_telefono,
                b.metodo_pago,
                b.referencia_pago,
                b.estado
                                
            FROM boletos b
            INNER JOIN clientes c ON b.cliente_id = c.id
            INNER JOIN viajes v ON b.viaje_id = v.id
            INNER JOIN rutas r ON v.ruta_id = r.id
            LEFT JOIN rutas_paradas rp ON b.parada_id = rp.id
            LEFT JOIN rutas_paradas rps ON b.parada_subida_id = rps.id
            LEFT JOIN terminales ter_orig ON v.terminal_origen_id = ter_orig.id
            LEFT JOIN vehiculos bus ON v.bus_id = bus.id
            LEFT JOIN terminales suc ON suc.id = b.sucursal_id
            WHERE b.id = :id";

            $this->db->query($sql);
            $this->db->bind(':id', $boletoId);

            return $this->db->single();
        } catch (Exception $e) {
            error_log("Error obtenerDatosTicket: " . $e->getMessage());
            return false;
        }
    }

    // Obtener lista de pasajeros para un viaje específico
    // ✅ SOLUCIÓN DEFINITIVA: Modelo robusto con manejo de errores y logging
    public function obtenerPasajerosPorViaje($viajeId)
    {
        try {
            $sql = "SELECT 
                        b.id,
                        b.numero_asiento,
                        b.codigo_boleto,
                        c.numero_documento,
                        b.precio_final as precio,
                        b.estado,
                        b.fecha_reserva as fecha_venta,
                        c.nombres,
                        c.apellidos,
                        CONCAT(c.nombres, ' ', c.apellidos) as nombre_pasajero,
                        c.celular as telefono,
                        v.fecha_salida,
                        v.hora_salida,
                        
                        -- Tramo del pasajero: donde sube y donde baja
                        COALESCE(rps.nombre_parada, r.origen) AS ciudad_origen,
                        COALESCE(rp.nombre_parada, r.destino) AS ciudad_destino,

                        -- Alias de compatibilidad para el controlador
                        COALESCE(rps.nombre_parada, r.origen) AS origen,
                        COALESCE(rp.nombre_parada, r.destino) AS destino,

                        COALESCE(bu.placa, 'SIN ASIGNAR') AS bus_placa
                      FROM boletos b
                      INNER JOIN clientes c ON b.cliente_id = c.id
                      INNER JOIN viajes v ON b.viaje_id = v.id
                      INNER JOIN rutas r ON v.ruta_id = r.id
                      LEFT JOIN rutas_paradas rp ON b.parada_id = rp.id
                      LEFT JOIN rutas_paradas rps ON b.parada_subida_id = rps.id
                      LEFT JOIN vehiculos bu ON v.bus_id = bu.id
                      WHERE b.viaje_id = :viaje_id
                        AND b.estado IN ('vendido', 'reservado')
                      ORDER BY CAST(b.numero_asiento AS UNSIGNED) ASC";

            $this->db->query($sql);
            $this->db->bind(':viaje_id', $viajeId);

            $resultado = $this->db->resultSet();

            // Log para debugging
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                error_log("✅ Pasajeros encontrados para viaje $viajeId: " . count($resultado));
            }

            return $resultado ? $resultado : [];
        } catch (PDOException $e) {
            error_log("❌ ERROR SQL en obtenerPasajerosPorViaje (viaje_id: $viajeId): " . $e->getMessage());
            throw new Exception("Error al consultar pasajeros: " . $e->getMessage());
        }
    }

    // Cancelar/Eliminar un boleto (Liberar asiento)
    public function cancelarBoleto($id)
    {
        $this->db->query("DELETE FROM boletos WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Actualiza una reserva existente para convertirla en venta (o actualizar datos)
     */
    public function actualizarReserva($id, $datos)
    {
        try {
            // 1. Verificar/Actualizar Cliente
            $clienteId = $this->buscarOCrearCliente($datos['nombre_pasajero'], $datos['documento_pasajero']);

            // 2. Actualizar Boleto
            $sql = "UPDATE boletos SET 
                    cliente_id = :cliente_id, 
                    estado = :estado, 
                    precio_final = :precio
                    WHERE id = :id";

            $this->db->query($sql);
            $this->db->bind(':cliente_id', $clienteId);
            $this->db->bind(':estado', $datos['estado']); // 'vendido'
            $this->db->bind(':precio', $datos['precio']);
            $this->db->bind(':id', $id);

            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Error actualizarReserva: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene datos crudos de un boleto para llenar el formulario
     */
    public function obtenerTicketPorId($id)
    {
        $this->db->query("SELECT 
                            b.id,
                            b.numero_asiento,
                            b.precio_final,
                            b.estado,
                            c.nombres,
                            c.apellidos,
                            c.numero_documento
                          FROM boletos b
                          INNER JOIN clientes c ON b.cliente_id = c.id
                          WHERE b.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Actualizar datos de cliente y precio desde Gestión Boleto
    public function actualizarDatosBoleto($boletoId, $nombres, $apellidos, $documento, $precio)
    {
        // 1. Obtener ID Cliente
        $this->db->query("SELECT cliente_id FROM boletos WHERE id = :id");
        $this->db->bind(':id', $boletoId);
        $res = $this->db->single();

        if ($res) {
            $clienteId = $res->cliente_id;
            // 2. Actualizar Cliente
            $this->db->query("UPDATE clientes SET nombres = :n, apellidos = :a, numero_documento = :d WHERE id = :cid");
            $this->db->bind(':n', $nombres);
            $this->db->bind(':a', $apellidos);
            $this->db->bind(':d', $documento);
            $this->db->bind(':cid', $clienteId);
            $this->db->execute();

            // 3. Actualizar Precio Boleto
            $this->db->query("UPDATE boletos SET precio_final = :p WHERE id = :bid");
            $this->db->bind(':p', $precio);
            $this->db->bind(':bid', $boletoId);
            return $this->db->execute();
        }
        return false;
    }

    /**
     * Confirma el pago de un boleto reservado (efectivo o QR) y lo registra en la
     * caja abierta del usuario. Antes, confirmar una reserva solo cambiaba el
     * estado a vendido y el ingreso nunca llegaba a caja.
     * @return object datos del ticket para imprimir
     */
    public function confirmarPagoBoleto($boletoId, $usuarioId, $metodo = 'EFECTIVO', $referencia = null)
    {
        $metodo = $metodo === 'QR' ? 'QR' : 'EFECTIVO';

        $this->db->beginTransaction();
        try {
            $this->db->query("SELECT id, estado, precio_final, codigo_boleto, numero_asiento, viaje_id FROM boletos WHERE id = :id FOR UPDATE");
            $this->db->bind(':id', $boletoId);
            $boleto = $this->db->single();

            if (!$boleto) {
                throw new Exception('El boleto no existe.');
            }
            if ($boleto->estado === 'vendido') {
                throw new Exception('Este boleto ya fue cobrado.');
            }
            if ($boleto->estado !== 'reservado') {
                throw new Exception('La reserva fue cancelada o venció. Vuelva a seleccionar el asiento.');
            }

            $this->db->query("SELECT id, sucursal_id FROM cajas_sesiones WHERE usuario_id = :uid AND estado = 'ABIERTA'");
            $this->db->bind(':uid', $usuarioId);
            $caja = $this->db->single();
            if (!$caja) {
                throw new Exception('No tiene una caja abierta. Abra caja para confirmar cobros.');
            }

            $this->db->query("UPDATE boletos SET estado = 'vendido', sesion_caja_id = :caja, sucursal_id = COALESCE(:sucursal, sucursal_id), metodo_pago = :metodo,
                                     referencia_pago = :ref, fecha_pago = NOW(), fecha_expiracion_reserva = NULL
                              WHERE id = :id");
            $this->db->bind(':caja', $caja->id);
            $this->db->bind(':sucursal', $caja->sucursal_id);
            $this->db->bind(':metodo', $metodo);
            $this->db->bind(':ref', $referencia !== null && $referencia !== '' ? mb_substr(trim($referencia), 0, 60) : null);
            $this->db->bind(':id', $boletoId);
            $this->db->execute();

            $this->db->query("INSERT INTO movimientos_caja (sesion_id, tipo_movimiento, origen_modulo, referencia_id, monto, metodo_pago, descripcion)
                              VALUES (:sesion, 'INGRESO', 'PASAJE', :ref, :monto, :metodo, :desc)");
            $this->db->bind(':sesion', $caja->id);
            $this->db->bind(':ref', $boletoId);
            $this->db->bind(':monto', $boleto->precio_final);
            $this->db->bind(':metodo', $metodo);
            $this->db->bind(':desc', "Venta Boleto #{$boleto->codigo_boleto} Asiento: {$boleto->numero_asiento}" . ($metodo === 'QR' ? ' (QR' . ($referencia ? " op. {$referencia}" : '') . ')' : ''));
            $this->db->execute();

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->obtenerDatosTicket($boletoId);
    }

    /** Cancela un cobro QR pendiente y libera el asiento (queda registrado como cancelado). */
    public function cancelarCobroQr($boletoId)
    {
        $this->db->query("UPDATE boletos SET estado = 'cancelado' WHERE id = :id AND estado = 'reservado' AND metodo_pago = 'QR'");
        $this->db->bind(':id', $boletoId);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    /** Libera asientos de cobros QR que no se confirmaron a tiempo. */
    public function liberarCobrosQrVencidos($viajeId = null)
    {
        $this->db->query("UPDATE boletos SET estado = 'cancelado'
                          WHERE estado = 'reservado' AND metodo_pago = 'QR'
                            AND fecha_expiracion_reserva IS NOT NULL AND fecha_expiracion_reserva < NOW()"
            . ($viajeId ? ' AND viaje_id = :vid' : ''));
        if ($viajeId) {
            $this->db->bind(':vid', $viajeId);
        }
        return $this->db->execute();
    }

    /** Estado de un boleto para la pantalla del pasajero (sin datos personales). */
    public function estadoCobroBoleto($boletoId)
    {
        $this->db->query("SELECT b.id, b.estado, b.metodo_pago, b.precio_final, b.numero_asiento, b.codigo_boleto, b.sucursal_id,
                                 GREATEST(TIMESTAMPDIFF(SECOND, NOW(), b.fecha_expiracion_reserva), 0) AS segundos_restantes,
                                 COALESCE(rps.nombre_parada, r.origen) AS origen, COALESCE(rp.nombre_parada, r.destino) AS destino, v.fecha_salida
                          FROM boletos b
                          JOIN viajes v ON v.id = b.viaje_id
                          JOIN rutas r ON r.id = v.ruta_id
                          LEFT JOIN rutas_paradas rp ON rp.id = b.parada_id
                          LEFT JOIN rutas_paradas rps ON rps.id = b.parada_subida_id
                          WHERE b.id = :id");
        $this->db->bind(':id', $boletoId);
        return $this->db->single() ?: null;
    }

    public function cambiarEstadoBoleto($id, $estado)
    {
        $this->db->query("UPDATE boletos SET estado = :e WHERE id = :id");
        $this->db->bind(':e', $estado);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Obtener conteo de asientos por estado
     */
    public function obtenerConteoAsientos($viajeId)
    {
        // 1. Obtener capacidad total
        $this->db->query("
            SELECT MAX(tb.capacidad) as capacidad 
            FROM viajes v 
            LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id 
            WHERE v.id = :id
        ");
        $this->db->bind(':id', $viajeId);
        $res = $this->db->single();
        $total = $res ? (int)$res->capacidad : 40;

        // 2. Contar por estado
        $this->db->query("
            SELECT estado, COUNT(*) as cantidad 
            FROM boletos 
            WHERE viaje_id = :viaje_id 
            GROUP BY estado
        ");
        $this->db->bind(':viaje_id', $viajeId);
        $filas = $this->db->resultSet();

        $vendidos = 0;
        $reservados = 0;

        foreach ($filas as $fila) {
            if ($fila->estado === 'vendido') $vendidos = (int)$fila->cantidad;
            if ($fila->estado === 'reservado') $reservados = (int)$fila->cantidad;
        }

        $libres = $total - ($vendidos + $reservados);
        if ($libres < 0) $libres = 0;

        return [
            'libres' => $libres,
            'reservados' => $reservados,
            'vendidos' => $vendidos
        ];
    }

    /** Choferes activos (personal con perfil Chofer) para programar viajes. */
    public function listarChoferes()
    {
        try {
            $this->db->query("SELECT id, nombres, apellidos FROM personal WHERE perfil = 'Chofer' AND estado = 1 ORDER BY apellidos, nombres");
            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log('RutaModel::listarChoferes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Helper privado para mantener actualizada la asignación de bus-chofer
     */
    private function actualizarAsignacionBus($busId, $choferId)
    {
        try {
            // 1. Verificar si ya existe una asignación activa para este bus
            $this->db->query("SELECT id FROM asignaciones_buses WHERE bus_id = :bus_id AND estado = 1");
            $this->db->bind(':bus_id', $busId);
            $asignacion = $this->db->single();

            if ($asignacion) {
                // Actualizar asignación existente
                $this->db->query("UPDATE asignaciones_buses SET chofer_id = :chofer_id WHERE id = :id");
                $this->db->bind(':chofer_id', $choferId);
                $this->db->bind(':id', $asignacion->id);
                $this->db->execute();
            } else {
                // Crear nueva asignación
                $this->db->query("INSERT INTO asignaciones_buses (bus_id, chofer_id, estado, fecha_asignacion) VALUES (:bus_id, :chofer_id, 1, NOW())");
                $this->db->bind(':bus_id', $busId);
                $this->db->bind(':chofer_id', $choferId);
                $this->db->execute();
            }
        } catch (Exception $e) {
            // Log silencioso, no detener el proceso principal si falla esto
            error_log("Error actualizando asignación automática: " . $e->getMessage());
        }
    }
    /**
     * ==========================================
     *  MÉTODOS PARA SISTEMA DE PARADAS Y ENCOMIENDAS
     * ==========================================
     */

    /**
     * Obtener paradas ordenadas de una ruta
     */
    public function obtenerParadasPorRuta($rutaId)
    {
        $this->db->query("SELECT * FROM rutas_paradas WHERE ruta_id = :ruta_id AND estado = 1 ORDER BY orden_index ASC");
        $this->db->bind(':ruta_id', $rutaId);
        return $this->db->resultSet();
    }

    /**
     * Obtener tipos de encomienda
     */
    public function obtenerTiposEncomienda()
    {
        $this->db->query("SELECT * FROM encomienda_tipos WHERE estado = 1 ORDER BY precio_extra ASC");
        return $this->db->resultSet();
    }

    /**
     * Calcular precio dinámico
     */
    public function calcularPrecioDinamico($paradaId, $tipo, $tipoPaqueteId = null)
    {
        $this->db->query("SELECT * FROM rutas_paradas WHERE id = :id");
        $this->db->bind(':id', $paradaId);
        $parada = $this->db->single();

        if (!$parada) {
            return ['status' => false, 'message' => 'Parada no encontrada'];
        }

        $precioFinal = 0.00;
        $detalles = [];

        if ($tipo === 'pasajero') {
            $precioFinal = (float) $parada->precio_pasaje;
            $detalles[] = "Pasaje a " . $parada->nombre_parada;
        } elseif ($tipo === 'encomienda') {
            $precioBase = (float) $parada->precio_base_encomienda;
            $precioFinal += $precioBase;
            $detalles[] = "Envío base a " . $parada->nombre_parada . " (" . number_format($precioBase, 2) . " Bs)";

            if ($tipoPaqueteId) {
                // Verificar si existe el paquete
                $this->db->query("SELECT * FROM encomienda_tipos WHERE id = :pid");
                $this->db->bind(':pid', $tipoPaqueteId);
                $paquete = $this->db->single();
                if ($paquete) {
                    $extra = (float) $paquete->precio_extra;
                    $precioFinal += $extra;
                    $detalles[] = "Tipo: " . $paquete->nombre . " (+" . number_format($extra, 2) . " Bs)";
                }
            }
        }

        return [
            'status' => true,
            'precio_total' => $precioFinal,
            'desglose' => implode(" + ", $detalles),
            'moneda' => 'Bs'
        ];
    }

    /**
     * Guardar una nueva parada (Para Gestión)
     */
    public function guardarParada($datos)
    {
        try {
            $sql = "INSERT INTO rutas_paradas (ruta_id, nombre_parada, orden_index, precio_pasaje, precio_base_encomienda) 
                    VALUES (:ruta, :nombre, :orden, :pasaje, :encomienda)";
            $this->db->query($sql);
            $this->db->bind(':ruta', $datos['ruta_id']);
            $this->db->bind(':nombre', $datos['nombre']);
            $this->db->bind(':orden', $datos['orden']);
            $this->db->bind(':pasaje', $datos['precio_pasaje']);
            $this->db->bind(':encomienda', $datos['precio_encomienda']);
            return $this->db->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Eliminar parada
     */
    public function eliminarParada($id)
    {
        $this->db->query("DELETE FROM rutas_paradas WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
