<?php
class VehiculoModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function registrarVehiculo($datos)
    {
        $this->db->query('INSERT INTO vehiculos (
            propietario_nombres, propietario_apellidos, tarjeta_circulacion, placa, clase, marca, anio, modelo,
            tipo_combustible, carroceria, ejes, color, nro_motor, cilindros, nro_serie, ruedas,
            peso_seco, peso_bruto, longitud, altura, ancho, pasajeros, asientos, tipo_servicio,
            estado, fecha_registro
        ) VALUES (
            :propietario_nombres, :propietario_apellidos, :tarjeta_circulacion, :placa, :clase, :marca, :anio, :modelo,
            :tipo_combustible, :carroceria, :ejes, :color, :nro_motor, :cilindros, :nro_serie, :ruedas,
            :peso_seco, :peso_bruto, :longitud, :altura, :ancho, :pasajeros, :asientos, :tipo_servicio,
            :estado, :fecha_registro
        )');

        // Vincular valores
        $this->db->bind(':propietario_nombres', $datos['propietario_nombres']);
        $this->db->bind(':propietario_apellidos', $datos['propietario_apellidos']);
        $this->db->bind(':tarjeta_circulacion', $datos['tarjeta_circulacion']);
        $this->db->bind(':placa', $datos['placa']);
        $this->db->bind(':clase', $datos['clase']);
        $this->db->bind(':marca', $datos['marca']);
        $this->db->bind(':anio', $datos['anio']);
        $this->db->bind(':modelo', $datos['modelo']);
        $this->db->bind(':tipo_combustible', $datos['tipo_combustible']);
        $this->db->bind(':carroceria', $datos['carroceria']);
        $this->db->bind(':ejes', $datos['ejes']);
        $this->db->bind(':color', $datos['color']);
        $this->db->bind(':nro_motor', $datos['nro_motor']);
        $this->db->bind(':cilindros', $datos['cilindros']);
        $this->db->bind(':nro_serie', $datos['nro_serie']);
        $this->db->bind(':ruedas', $datos['ruedas']);
        $this->db->bind(':peso_seco', $datos['peso_seco']);
        $this->db->bind(':peso_bruto', $datos['peso_bruto']);
        $this->db->bind(':longitud', $datos['longitud']);
        $this->db->bind(':altura', $datos['altura']);
        $this->db->bind(':ancho', $datos['ancho']);
        $this->db->bind(':pasajeros', $datos['pasajeros']);
        $this->db->bind(':asientos', $datos['asientos']);
        $this->db->bind(':tipo_servicio', $datos['tipo_servicio']);
        $this->db->bind(':estado', 1); // 1 = Activo
        $this->db->bind(':fecha_registro', date('Y-m-d H:i:s'));

        // Ejecutar
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function obtenerVehiculos()
    {
        $this->db->query('SELECT * FROM vehiculos ORDER BY fecha_registro DESC');
        return $this->db->resultSet();
    }

    // Función extra para validar duplicados (recomendada)
    public function existePlaca($placa)
    {
        $this->db->query('SELECT id FROM vehiculos WHERE placa = :placa');
        $this->db->bind(':placa', $placa);
        $this->db->single();
        return $this->db->rowCount() > 0;
    }

    public function listarVehiculos()
    {
        $this->db->query('SELECT * FROM vehiculos WHERE estado = 1 ORDER BY id DESC');
        return $this->db->resultSet();
    }

    /**
     * Obtener un bus por su ID
     * @param int $id - ID del bus
     * @return object|false - Objeto con los datos del bus o false si no existe
     */
    public function obtenerBusPorId($id)
    {
        $this->db->query('SELECT * FROM vehiculos WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Actualizar los datos de un bus existente
     * @param array $datos - Array con los datos del bus a actualizar
     * @return bool - true si se actualizó correctamente, false en caso contrario
     */
    public function actualizarBus($datos)
    {
        $this->db->query('UPDATE vehiculos SET
            propietario_nombres = :propietario_nombres,
            propietario_apellidos = :propietario_apellidos,
            tarjeta_circulacion = :tarjeta_circulacion,
            placa = :placa,
            clase = :clase,
            marca = :marca,
            anio = :anio,
            modelo = :modelo,
            tipo_combustible = :tipo_combustible,
            carroceria = :carroceria,
            ejes = :ejes,
            color = :color,
            nro_motor = :nro_motor,
            cilindros = :cilindros,
            nro_serie = :nro_serie,
            ruedas = :ruedas,
            peso_seco = :peso_seco,
            peso_bruto = :peso_bruto,
            longitud = :longitud,
            altura = :altura,
            ancho = :ancho,
            pasajeros = :pasajeros,
            asientos = :asientos,
            tipo_servicio = :tipo_servicio
        WHERE id = :id');

        // Vincular valores
        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':propietario_nombres', $datos['propietario_nombres']);
        $this->db->bind(':propietario_apellidos', $datos['propietario_apellidos']);
        $this->db->bind(':tarjeta_circulacion', $datos['tarjeta_circulacion']);
        $this->db->bind(':placa', $datos['placa']);
        $this->db->bind(':clase', $datos['clase']);
        $this->db->bind(':marca', $datos['marca']);
        $this->db->bind(':anio', $datos['anio']);
        $this->db->bind(':modelo', $datos['modelo']);
        $this->db->bind(':tipo_combustible', $datos['tipo_combustible']);
        $this->db->bind(':carroceria', $datos['carroceria']);
        $this->db->bind(':ejes', $datos['ejes']);
        $this->db->bind(':color', $datos['color']);
        $this->db->bind(':nro_motor', $datos['nro_motor']);
        $this->db->bind(':cilindros', $datos['cilindros']);
        $this->db->bind(':nro_serie', $datos['nro_serie']);
        $this->db->bind(':ruedas', $datos['ruedas']);
        $this->db->bind(':peso_seco', $datos['peso_seco']);
        $this->db->bind(':peso_bruto', $datos['peso_bruto']);
        $this->db->bind(':longitud', $datos['longitud']);
        $this->db->bind(':altura', $datos['altura']);
        $this->db->bind(':ancho', $datos['ancho']);
        $this->db->bind(':pasajeros', $datos['pasajeros']);
        $this->db->bind(':asientos', $datos['asientos']);
        $this->db->bind(':tipo_servicio', $datos['tipo_servicio']);

        // Ejecutar
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Eliminar (desactivar) un bus
     * @param int $id - ID del bus a eliminar
     * @return bool - true si se eliminó correctamente, false en caso contrario
     */
    public function eliminarBus($id)
    {
        // Cambiar el estado del bus a 0 (inactivo) en lugar de eliminarlo físicamente
        $this->db->query('UPDATE vehiculos SET estado = 0 WHERE id = :id');
        $this->db->bind(':id', $id);

        // Ejecutar
        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }
}
