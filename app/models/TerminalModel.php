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
}
