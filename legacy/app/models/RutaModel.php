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
    public function obtenerAsientosOcupados($viajeId)
    {
        try {
            // Consultar asientos vendidos o reservados desde la tabla CORRECTA 'boletos'
            $this->db->query("SELECT id, numero_asiento, estado FROM boletos WHERE viaje_id = :viaje_id AND estado IN ('vendido', 'reservado')");
            $this->db->bind(':viaje_id', $viajeId);

            $resultados = $this->db->resultSet();

            // Transformar a array de objetos para manejar estados
            $ocupados = [];
            foreach ($resultados as $fila) {
                $ocupados[] = [
                    'id' => $fila->id,
                    'numero' => (int) $fila->numero_asiento,
                    'estado' => $fila->estado
                ];
            }

            return $ocupados;
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
                // ✅ ACTUALIZACIÓN DINÁMICA: Si se seleccionó bus y chofer, actualizar la asignación permanente
                if ($busId && $choferId) {
                    $this->actualizarAsignacionBus($busId, $choferId);
                }
                return true;
            } else {
                return "Error al guardar la ruta de viaje";
            }
        } catch (PDOException $e) {
            return "Error de base de datos: " . $e->getMessage();
        }
    }

    /**
     * Obtener buses disponibles por tipo de bus
     */
    public function obtenerBusesPorTipo($tipoBusId)
    {
        /* 
           CORRECCIÓN APLICADA: Adaptación a estructura real de tabla 'vehiculos'.
           
           1. Se elimina 'numero_interno' (no existe) -> Se usa 'id' como fallback.
           2. Se elimina filtro 'tipo_bus_id' (no existe) -> Se muestran todos los buses activos.
           3. Se añaden campos útiles existentes: 'modelo', 'asientos', 'tipo_servicio'.
        */
        $this->db->query("SELECT id, placa, marca, modelo, asientos, tipo_servicio, id as numero_interno FROM vehiculos WHERE estado = 1");
        // $this->db->bind(':tipo_id', $tipoBusId); // Filtro deshabilitado temporalmente por incompatibilidad de esquema
        return $this->db->resultSet();
    }

    /**
     * Obtener tripulación asignada a un bus (desde asignaciones_buses)
     * ✅ VERSIÓN MEJORADA CON AUTO-CREACIÓN Y DATOS COMPLETOS DEL BUS
     * 
     * Funcionalidad:
     * 1. Busca la asignación existente
     * 2. Si no existe, la crea automáticamente (failsafe)
     * 3. Retorna información completa del bus (placa, chofer, tipo, etc.)
     * 
     * @param int $busId ID del bus
     * @return object|false Datos de la tripulación y bus, o false si el bus no existe
     */
    public function obtenerTripulacionBus($busId)
    {
        try {
            // Primero intentar obtener la asignación existente con toda la info del bus
            // CORRECCIÓN: Adaptado para tabla 'vehiculos' sin 'numero_interno' ni 'tipo_bus_id'
            $this->db->query("
                SELECT 
                    -- Datos de la asignación
                    ab.bus_id,
                    ab.chofer_id,
                    CONCAT(u.nombres, ' ', u.apellidos) as nombre_chofer,
                    ab.copiloto_id,
                    CONCAT(u2.nombres, ' ', u2.apellidos) as nombre_copiloto,
                    
                    -- ✅ Información completa del bus (Desde vehiculos)
                    b.placa as bus_placa,
                    b.id as bus_numero,  -- Fallback: ID en lugar de numero_interno
                    b.marca as bus_marca,
                    b.modelo as bus_modelo,
                    
                    -- ✅ Información del tipo de bus (Mapeada desde vehiculos)
                    b.tipo_servicio as tipo_bus, -- Fallback: tipo_servicio en lugar de join con tipos_buses
                    1 as bus_pisos,              -- Default: 1 piso
                    b.asientos as bus_capacidad  -- Capacidad directa de vehiculos
                    
                FROM asignaciones_buses ab
                INNER JOIN vehiculos b ON ab.bus_id = b.id
                -- LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id -- REMOVIDO: No hay FK
                LEFT JOIN usuarios u ON ab.chofer_id = u.id
                LEFT JOIN usuarios u2 ON ab.copiloto_id = u2.id
                WHERE ab.bus_id = :bus_id AND ab.estado = 1
                LIMIT 1
            ");
            $this->db->bind(':bus_id', $busId);
            $resultado = $this->db->single();

            // ✅ AUTO-CREACIÓN: Si no existe asignación, crearla automáticamente
            if (!$resultado) {
                error_log("⚠️ AVISO: Bus ID $busId no tiene asignación de tripulación. Creando asignación automática...");

                // Verificar que el bus existe antes de crear la asignación
                $this->db->query("SELECT id FROM vehiculos WHERE id = :bus_id");
                $this->db->bind(':bus_id', $busId);
                $busExiste = $this->db->single();

                if (!$busExiste) {
                    error_log("❌ ERROR: Bus ID $busId no existe en la base de datos");
                    return false;
                }

                // Crear asignación vacía (sin chofer asignado aún)
                $this->db->query("
                    INSERT INTO asignaciones_buses (bus_id, chofer_id, copiloto_id, estado, fecha_asignacion)
                    VALUES (:bus_id, NULL, NULL, 1, NOW())
                ");
                $this->db->bind(':bus_id', $busId);

                if ($this->db->execute()) {
                    error_log("✅ Asignación creada exitosamente para bus ID $busId");

                    // Obtener la información del bus (sin chofer)
                    // CORRECCIÓN: Adaptado para tabla 'vehiculos'
                    $this->db->query("
                        SELECT 
                            b.id as bus_id,
                            NULL as chofer_id,
                            NULL as nombre_chofer,
                            NULL as copiloto_id,
                            NULL as nombre_copiloto,
                            b.placa as bus_placa,
                            b.id as bus_numero, -- Utilizar ID como número
                            b.marca as bus_marca,
                            b.modelo as bus_modelo,
                            b.tipo_servicio as tipo_bus,
                            1 as bus_pisos,
                            b.asientos as bus_capacidad
                        FROM vehiculos b
                        -- LEFT JOIN tipos_buses tb ON b.tipo_bus_id = tb.id
                        WHERE b.id = :bus_id
                        LIMIT 1
                    ");
                    $this->db->bind(':bus_id', $busId);
                    $resultado = $this->db->single();
                } else {
                    error_log("❌ ERROR: No se pudo crear asignación para bus ID $busId");
                    return false;
                }
            }

            if ($resultado) {
                // SANITIZACIÓN: Si hay ID de chofer pero no se encontró nombre (usuario borrado),
                // forzamos NULL para permitir reasignación en el frontend.
                if (!empty($resultado->chofer_id) && empty($resultado->nombre_chofer)) {
                    $resultado->chofer_id = null;
                }
            }

            return $resultado;
        } catch (PDOException $e) {
            error_log("❌ ERROR SQL en obtenerTripulacionBus: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("❌ ERROR GENERAL en obtenerTripulacionBus: " . $e->getMessage());
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
                    CONCAT(u1.nombres, ' ', u1.apellidos) as chofer_nombre,
                    b.placa as bus_placa,
                    b.id as bus_numero
                FROM viajes v
                INNER JOIN rutas r ON v.ruta_id = r.id
                LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
                LEFT JOIN terminales to_term ON v.terminal_origen_id = to_term.id
                LEFT JOIN terminales td_term ON v.terminal_destino_id = td_term.id
                LEFT JOIN usuarios u1 ON v.chofer_id = u1.id
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
                COALESCE(CONCAT(u1.nombres, ' ', u1.apellidos), CONCAT(p_chofer.nombres, ' ', p_chofer.apellidos), 'PENDIENTE DE ASIGNACIÓN') as chofer_nombre,
                COALESCE(CONCAT(p_copiloto.nombres, ' ', p_copiloto.apellidos), 'PENDIENTE DE ASIGNACIÓN') as copiloto_nombre,
                COALESCE(b.placa, 'PENDIENTE DE ASIGNACIÓN') as bus_placa,
                COALESCE(b.id, 'S/N') as bus_numero
            FROM viajes v
            INNER JOIN rutas r ON v.ruta_id = r.id
            LEFT JOIN tipos_buses tb ON v.tipo_bus_id = tb.id
            LEFT JOIN terminales term_origen ON v.terminal_origen_id = term_origen.id
            LEFT JOIN terminales term_destino ON v.terminal_destino_id = term_destino.id
            LEFT JOIN vehiculos b ON v.bus_id = b.id
            LEFT JOIN usuarios u1 ON v.chofer_id = u1.id
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

            // 1. Obtener ID de caja abierta
            $this->db->query("SELECT id FROM cajas_sesiones WHERE usuario_id = :uid AND estado = 'ABIERTA'");
            $this->db->bind(':uid', $datos['usuario_id']);
            $cajaAbierta = $this->db->single();

            // Si es una VENTA (no reserva), requerir caja abierta
            if ($datos['estado'] == 'vendido') {
                if (!$cajaAbierta) {
                    throw new Exception("No tienes una Caja Abierta. Debes abrir caja para realizar ventas.");
                }
                $sesionId = $cajaAbierta->id;
            } else {
                $sesionId = null; // Reservas no mueven dinero aún
            }

            // 2. Verificar Asiento Disponible (Bloqueo para concurrencia)
            $this->db->query("SELECT id FROM boletos WHERE viaje_id = :vid AND numero_asiento = :asiento AND estado IN ('vendido','reservado') FOR UPDATE");
            $this->db->bind(':vid', $datos['viaje_id']);
            $this->db->bind(':asiento', $datos['asiento']);
            if ($this->db->single()) {
                throw new Exception("El asiento " . $datos['asiento'] . " ya está ocupado en el viaje #" . $datos['viaje_id'] . ". Seleccione otro.");
            }

            // 3. Generar Código
            $codigo = $this->generarCodigoBoleto();

            // 4. Insertar Boleto
            $paradaId = !empty($datos['parada_id']) ? $datos['parada_id'] : null;

            $sql = "INSERT INTO boletos (viaje_id, cliente_id, usuario_vendedor_id, sesion_caja_id, numero_asiento, precio_final, estado, fecha_reserva, codigo_boleto, parada_id) 
                    VALUES (:vid, :cid, :uid, :sesion, :asiento, :precio, :estado, NOW(), :codigo, :parada_id)";

            $this->db->query($sql);
            $this->db->bind(':vid', $datos['viaje_id']);
            $this->db->bind(':cid', $clienteId);
            $this->db->bind(':uid', $datos['usuario_id']);
            $this->db->bind(':sesion', $sesionId);
            $this->db->bind(':asiento', $datos['asiento']);
            $this->db->bind(':precio', $datos['precio']);
            $this->db->bind(':estado', $datos['estado']);
            $this->db->bind(':codigo', $codigo);
            $this->db->bind(':parada_id', $paradaId);

            $this->db->execute();
            $boletoId = $this->db->lastInsertId();

            // 5. REGISTRAR MOVIMIENTO DE CAJA (Solo si es venta)
            if ($datos['estado'] == 'vendido' && $sesionId) {
                // Insertar movimiento
                $sqlCaja = "INSERT INTO movimientos_caja (sesion_id, tipo_movimiento, origen_modulo, referencia_id, monto, descripcion) 
                            VALUES (:sesion, 'INGRESO', 'PASAJE', :ref, :monto, :desc)";

                $descripcion = "Venta Boleto #" . $codigo . " Asiento: " . $datos['asiento'];

                $this->db->query($sqlCaja);
                $this->db->bind(':sesion', $sesionId);
                $this->db->bind(':ref', $boletoId);
                $this->db->bind(':monto', $datos['precio']);
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
                r.origen as ciudad_origen,
                COALESCE(rp.nombre_parada, r.destino) as ciudad_destino,
                
                -- Nombres de Terminales (Opcional, pero útil)
                ter_orig.nombre_sede as terminal_origen,
                
                -- Datos del Bus
                bus.placa as bus_placa,
                bus.numero_interno as bus_numero
                
            FROM boletos b
            INNER JOIN clientes c ON b.cliente_id = c.id
            INNER JOIN viajes v ON b.viaje_id = v.id
            INNER JOIN rutas r ON v.ruta_id = r.id
            LEFT JOIN rutas_paradas rp ON b.parada_id = rp.id
            LEFT JOIN terminales ter_orig ON v.terminal_origen_id = ter_orig.id
            LEFT JOIN buses bus ON v.bus_id = bus.id
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
                        
                        -- Corrección: Obtener nombres desde tabla terminales o paradas
                        t_origen.nombre_sede AS ciudad_origen,
                        COALESCE(rp.nombre_parada, t_destino.nombre_sede, r.destino) AS ciudad_destino,
                        
                        -- Alias de compatibilidad para el controlador
                        t_origen.nombre_sede AS origen,
                        COALESCE(rp.nombre_parada, t_destino.nombre_sede, r.destino) AS destino,

                        COALESCE(bu.placa, 'SIN ASIGNAR') AS bus_placa
                      FROM boletos b
                      INNER JOIN clientes c ON b.cliente_id = c.id
                      INNER JOIN viajes v ON b.viaje_id = v.id
                      INNER JOIN rutas r ON v.ruta_id = r.id
                      LEFT JOIN rutas_paradas rp ON b.parada_id = rp.id
                      LEFT JOIN terminales t_origen ON v.terminal_origen_id = t_origen.id
                      LEFT JOIN terminales t_destino ON v.terminal_destino_id = t_destino.id
                      LEFT JOIN buses bu ON v.bus_id = bu.id
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

    /**
     * Listar todos los choferes activos
     */
    public function listarChoferes()
    {
        try {
            // Asumimos tabla usuarios. Ajustar WHERE si hay columna de rol específica.
            // Ejemplo: WHERE rol = 'chofer' AND estado = 1
            $this->db->query("SELECT id, nombres, apellidos FROM usuarios WHERE estado = 1");
            return $this->db->resultSet();
        } catch (Exception $e) {
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
