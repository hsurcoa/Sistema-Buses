<?php

class EncomiendaModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Obtener tarifa para una ruta específica
     */
    public function obtenerTarifaPorRuta($rutaId)
    {
        $this->db->query("SELECT * FROM tarifas_encomienda WHERE ruta_id = :ruta_id AND estado = 1 LIMIT 1");
        $this->db->bind(':ruta_id', $rutaId);
        return $this->db->single();
    }

    /**
     * Listar encomiendas (Dashboard)
     */
    public function listarEncomiendas($limit = 100, $offset = 0)
    {
        $sql = "SELECT 
                e.*,
                v.fecha_salida,
                r.origen,
                r.destino,
                d.descripcion as contenido_breve
                FROM encomiendas e
                INNER JOIN viajes v ON e.viaje_id = v.id
                INNER JOIN rutas r ON v.ruta_id = r.id
                LEFT JOIN detalles_encomienda d ON d.encomienda_id = e.id
                ORDER BY e.id DESC
                LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    /**
     * Registrar una nueva encomienda con lógica de negocio y transacción
     */
    public function registrarEncomienda($datos)
    {
        try {
            $this->db->beginTransaction();

            // 0. VERIFICAR CAJA ABIERTA
            $this->db->query("SELECT id FROM cajas_sesiones WHERE usuario_id = :uid AND estado = 'ABIERTA'");
            $this->db->bind(':uid', $datos['usuario_id']);
            $cajaAbierta = $this->db->single();

            if (!$cajaAbierta) {
                throw new Exception("No tienes una Caja Abierta. Debes abrir caja para registrar encomiendas.");
            }
            $sesionId = $cajaAbierta->id;

            // 1. Validaciones de Negocio (Continuar...)
            // Verificar Viaje
            $this->db->query("SELECT id, ruta_id, estado, bus_id FROM viajes WHERE id = :id");
            $this->db->bind(':id', $datos['viaje_id']);
            $viaje = $this->db->single();

            if (!$viaje) {
                throw new Exception("El viaje seleccionado no existe");
            }
            if ($viaje->estado == 'Finalizado' || $viaje->estado == 'Cancelado') {
                throw new Exception("No se pueden registrar encomiendas en viajes finalizados o cancelados");
            }

            // Validar Capacidad (Simplificada: Sumar pesos)
            // Obtenemos capacidad del bus (si existe asignado)
            $capacidadMax = 1000; // Default 1 tonelada si no hay dato
            if ($viaje->bus_id) {
                // Podríamos buscar en tabla vehiculos si tuvieramos campo capacidad_carga, asumo default por ahora
            }

            $this->db->query("SELECT SUM(peso_kg) as total FROM detalles_encomienda de 
                              INNER JOIN encomiendas e ON de.encomienda_id = e.id 
                              WHERE e.viaje_id = :viaje_id AND e.estado != 'CANCELADO'");
            $this->db->bind(':viaje_id', $datos['viaje_id']);
            $resPeso = $this->db->single();
            $pesoActual = $resPeso->total ?? 0;

            if (($pesoActual + $datos['peso']) > $capacidadMax) {
                throw new Exception("Capacidad de bodega excedida. Disponible: " . ($capacidadMax - $pesoActual) . "kg");
            }

            // 2. Calcular Precios (Re-validación Simplificada)
            // Usamos el precio ya calculado por RutaModel (Base + Tipo Paquete)
            $totalBase = $datos['precio_verificado'] ?? 0;

            // Recargo por seguro (opcional, manteniendo lógica original)
            // Si no tenemos tarifas globales, asumimos un default del 2%
            $porcentajeSeguro = 2.00;

            // Intentar obtener configuracion si existe
            // $tarifa = $this->obtenerTarifaPorRuta($viaje->ruta_id);
            // if($tarifa) $porcentajeSeguro = $tarifa->porcentaje_seguro;

            $costoSeguro = $datos['valor_declarado'] * ($porcentajeSeguro / 100);

            // Nota: En el nuevo modelo, el "peso" ya no multiplica por kg explícitamente a menos que 
            // el "Tipo de Paquete" lo contemple. Si se desea cobrar exceso de peso, se debería agregar lógica extra.
            // Por ahora, asumimos que el precio verificado es el principal.

            $totalCalculado = $totalBase + $costoSeguro;

            // 3. Generar Código Guía
            $codigoGuia = $this->generarCodigoGuia();

            // 4. Insertar Encomienda (Cabecera)
            $sqlEnc = "INSERT INTO encomiendas 
                      (codigo_guia, viaje_id, remitente_nombre, remitente_dni, 
                       destinatario_nombre, destinatario_dni, destinatario_telefono,
                       clave_retiro, usuario_creacion_id, total_pagar, estado)
                       VALUES 
                       (:codigo, :viaje, :rem_nom, :rem_dni,
                        :dest_nom, :dest_dni, :dest_tel,
                        :clave, :usuario, :total, 'REGISTRADO')";

            $this->db->query($sqlEnc);
            $this->db->bind(':codigo', $codigoGuia);
            $this->db->bind(':viaje', $datos['viaje_id']);
            $this->db->bind(':rem_nom', $datos['remitente_nombre']);
            $this->db->bind(':rem_dni', $datos['remitente_dni']);
            $this->db->bind(':dest_nom', $datos['destinatario_nombre']);
            $this->db->bind(':dest_dni', $datos['destinatario_dni']);
            $this->db->bind(':dest_tel', $datos['destinatario_telefono']);
            $this->db->bind(':clave', $datos['clave_retiro']); // Puede ser null
            $this->db->bind(':usuario', $datos['usuario_id']);
            $this->db->bind(':total', $totalCalculado);

            $this->db->execute();
            $encomiendaId = $this->db->lastInsertId();

            // 5. Insertar Detalle
            $sqlDet = "INSERT INTO detalles_encomienda 
                      (encomienda_id, descripcion, peso_kg, tipo_carga, valor_declarado, precio_calculado)
                      VALUES 
                      (:enc_id, :desc, :peso, :tipo, :valor, :precio)";

            $this->db->query($sqlDet);
            $this->db->bind(':enc_id', $encomiendaId);
            $this->db->bind(':desc', $datos['descripcion']);
            $this->db->bind(':peso', $datos['peso']);
            $this->db->bind(':tipo', $datos['tipo_carga']);
            $this->db->bind(':valor', $datos['valor_declarado']);
            $this->db->bind(':precio', $totalCalculado); // Por simplicidad asignamos todo el precio al único item

            $this->db->execute();

            // 6. REGISTRAR MOVIMIENTO DE CAJA
            $sqlCaja = "INSERT INTO movimientos_caja (sesion_id, tipo_movimiento, origen_modulo, referencia_id, monto, descripcion) 
                        VALUES (:sesion, 'INGRESO', 'ENCOMIENDA', :ref, :monto, :desc)";

            $descripcion = "Encomienda Guía #" . $codigoGuia;

            $this->db->query($sqlCaja);
            $this->db->bind(':sesion', $sesionId);
            $this->db->bind(':ref', $encomiendaId);
            $this->db->bind(':monto', $totalCalculado);
            $this->db->bind(':desc', $descripcion);
            $this->db->execute();

            $this->db->commit();

            return [
                'status' => true,
                'id' => $encomiendaId,
                'guia' => $codigoGuia,
                'total' => $totalCalculado
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function generarCodigoGuia()
    {
        // ENC-AÑO-RANDOM
        return "ENC-" . date('Y') . "-" . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function obtenerEncomiendaPorId($id)
    {
        $this->db->query("SELECT e.*, d.*, v.fecha_salida, r.origen, r.destino 
                          FROM encomiendas e
                          LEFT JOIN detalles_encomienda d ON d.encomienda_id = e.id
                          INNER JOIN viajes v ON e.viaje_id = v.id
                          INNER JOIN rutas r ON v.ruta_id = r.id
                          WHERE e.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function listarViajesFuturosPorRuta($rutaId)
    {
        $this->db->query("SELECT v.id, v.fecha_salida, v.hora_salida, b.placa 
                          FROM viajes v 
                          LEFT JOIN vehiculos b ON v.bus_id = b.id
                          WHERE v.ruta_id = :ruta AND v.estado IN ('Programado', 'Activo') 
                          AND CONCAT(v.fecha_salida, ' ', v.hora_salida) > NOW()
                          ORDER BY v.fecha_salida ASC");
        $this->db->bind(':ruta', $rutaId);
        return $this->db->resultSet();
    }
}
