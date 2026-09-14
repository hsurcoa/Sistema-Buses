<?php
class TerminalModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function listarTerminales()
    {
        $this->db->query("SELECT * FROM terminales ORDER BY nombre_sede ASC");
        return $this->db->resultSet();
    }

    public function obtenerTerminal($id)
    {
        $this->db->query("SELECT * FROM terminales WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function agregarTerminal($data)
    {
        $this->db->query("INSERT INTO terminales (nombre_sede, direccion, numero_oficina, estado) VALUES (:nombre, :direccion, :oficina, :estado)");
        $this->db->bind(':nombre', $data['nombre_sede']);
        $this->db->bind(':direccion', $data['direccion']);
        $this->db->bind(':oficina', $data['numero_oficina']);
        $this->db->bind(':estado', 1);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function actualizarTerminal($data)
    {
        $this->db->query("UPDATE terminales SET nombre_sede = :nombre, direccion = :direccion, numero_oficina = :oficina WHERE id = :id");
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':nombre', $data['nombre_sede']);
        $this->db->bind(':direccion', $data['direccion']);
        $this->db->bind(':oficina', $data['numero_oficina']);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function cambiarEstado($id, $estado)
    {
        $this->db->query("UPDATE terminales SET estado = :estado WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':estado', $estado);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function eliminarTerminal($id)
    {
        $this->db->query("DELETE FROM terminales WHERE id = :id");
        $this->db->bind(':id', $id);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    // ---------------- Sucursales (multisucursal) ----------------

    /** Sucursales con cuantos usuarios, cajas, boletos y encomiendas tienen. */
    public function listarSucursales()
    {
        $this->db->query("SELECT t.*,
                                 (SELECT COUNT(*) FROM usuarios u WHERE u.sucursal_id = t.id AND u.estado = 'activo') AS usuarios,
                                 (SELECT COUNT(*) FROM cajas_sesiones c WHERE c.sucursal_id = t.id) AS cajas,
                                 (SELECT COUNT(*) FROM boletos b WHERE b.sucursal_id = t.id) AS boletos,
                                 (SELECT COUNT(*) FROM encomiendas e WHERE e.sucursal_origen_id = t.id OR e.sucursal_destino_id = t.id) AS encomiendas,
                                 (SELECT COUNT(*) FROM viajes v WHERE v.terminal_origen_id = t.id OR v.terminal_destino_id = t.id) AS viajes
                          FROM terminales t
                          ORDER BY t.estado DESC, t.nombre_sede");
        return $this->db->resultSet();
    }

    public function prefijoEnUso($prefijo, $excluirId = 0)
    {
        $this->db->query("SELECT id FROM terminales WHERE prefijo_boleto = :p AND id <> :id");
        $this->db->bind(':p', $prefijo);
        $this->db->bind(':id', (int) $excluirId);
        return (bool) $this->db->single();
    }

    public function guardarSucursal($d)
    {
        $campos = 'nombre_sede = :nombre, direccion = :direccion, numero_oficina = :oficina, telefono = :telefono,
                   prefijo_boleto = :prefijo, pago_qr_titular = :qr_titular, pago_qr_entidad = :qr_entidad'
            . (array_key_exists('pago_qr_imagen', $d) ? ', pago_qr_imagen = :qr_imagen' : '');

        if (!empty($d['id'])) {
            $this->db->query("UPDATE terminales SET {$campos} WHERE id = :id");
            $this->db->bind(':id', $d['id']);
        } else {
            $this->db->query("INSERT INTO terminales SET {$campos}, estado = 1");
        }
        $this->db->bind(':nombre', $d['nombre_sede']);
        $this->db->bind(':direccion', $d['direccion']);
        $this->db->bind(':oficina', $d['numero_oficina']);
        $this->db->bind(':telefono', $d['telefono'] !== '' ? $d['telefono'] : null);
        $this->db->bind(':prefijo', $d['prefijo_boleto']);
        $this->db->bind(':qr_titular', $d['pago_qr_titular'] !== '' ? $d['pago_qr_titular'] : null);
        $this->db->bind(':qr_entidad', $d['pago_qr_entidad'] !== '' ? $d['pago_qr_entidad'] : null);
        if (array_key_exists('pago_qr_imagen', $d)) {
            $this->db->bind(':qr_imagen', $d['pago_qr_imagen']);
        }
        return $this->db->execute();
    }

    /** Solo se borran sucursales sin ningun historial; con historial se desactivan. */
    public function eliminarSucursalSinHistorial($id)
    {
        $this->db->query("DELETE FROM terminales WHERE id = :id
                            AND NOT EXISTS (SELECT 1 FROM usuarios WHERE sucursal_id = :a)
                            AND NOT EXISTS (SELECT 1 FROM cajas_sesiones WHERE sucursal_id = :b)
                            AND NOT EXISTS (SELECT 1 FROM boletos WHERE sucursal_id = :c)
                            AND NOT EXISTS (SELECT 1 FROM encomiendas WHERE sucursal_origen_id = :d OR sucursal_destino_id = :e)
                            AND NOT EXISTS (SELECT 1 FROM viajes WHERE terminal_origen_id = :f OR terminal_destino_id = :g)
                            AND NOT EXISTS (SELECT 1 FROM series_boletos WHERE sede_id = :h)");
        $this->db->bind(':id', $id);
        foreach (['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'] as $k) {
            $this->db->bind(':' . $k, $id);
        }
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }
}
