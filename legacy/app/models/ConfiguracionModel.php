<?php
class ConfiguracionModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Obtener todas las configuraciones como array asociativo
    public function obtenerConfiguracion()
    {
        $this->db->query("SELECT clave, valor FROM configuracion_sistema");
        $resultados = $this->db->resultSet();

        $config = [];
        foreach ($resultados as $row) {
            $config[$row->clave] = $row->valor;
        }
        return $config;
    }

    /**
     * Datos del cobro QR para una sucursal: usa el QR propio de la sucursal si lo
     * tiene y, si no, el QR general del dueño. Devuelve null si el cobro QR esta
     * desactivado o no hay ninguna imagen cargada.
     */
    public function obtenerPagoQr($sucursalId = null)
    {
        $config = $this->obtenerConfiguracion();
        if (empty($config['pago_qr_activo'])) {
            return null;
        }

        $imagen = $config['pago_qr_imagen'] ?? '';
        $titular = $config['pago_qr_titular'] ?? '';
        $entidad = $config['pago_qr_entidad'] ?? '';
        $origen = 'general';

        if ($sucursalId) {
            $this->db->query("SELECT pago_qr_imagen, pago_qr_titular, pago_qr_entidad FROM terminales WHERE id = :id");
            $this->db->bind(':id', $sucursalId);
            $suc = $this->db->single();
            if ($suc && !empty($suc->pago_qr_imagen)) {
                $imagen = $suc->pago_qr_imagen;
                $titular = $suc->pago_qr_titular ?: $titular;
                $entidad = $suc->pago_qr_entidad ?: $entidad;
                $origen = 'sucursal';
            }
        }

        if ($imagen === '') {
            return null;
        }

        return [
            'imagen_ruta' => $imagen,
            'imagen' => URLROOT . '/' . $imagen,
            'titular' => $titular,
            'entidad' => $entidad,
            'instrucciones' => $config['pago_qr_instrucciones'] ?? '',
            'minutos' => max(3, (int) ($config['pago_qr_minutos'] ?? 15)),
            'origen' => $origen,
        ];
    }

    // Guardar o actualizar una configuración
    public function guardarConfiguracion($datos)
    {
        try {
            $this->db->beginTransaction();

            foreach ($datos as $clave => $valor) {
                // Verificar si existe
                $this->db->query("SELECT id FROM configuracion_sistema WHERE clave = :clave");
                $this->db->bind(':clave', $clave);

                if ($this->db->single()) {
                    // Update
                    $this->db->query("UPDATE configuracion_sistema SET valor = :valor WHERE clave = :clave");
                } else {
                    // Insert
                    $this->db->query("INSERT INTO configuracion_sistema (clave, valor) VALUES (:clave, :valor)");
                }

                $this->db->bind(':clave', $clave);
                $this->db->bind(':valor', $valor);
                $this->db->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
