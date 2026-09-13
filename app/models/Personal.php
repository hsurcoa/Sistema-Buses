<?php

class Personal
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function obtenerPersonal()
    {
        $this->db->query("SELECT * FROM personal ORDER BY created_at DESC");
        return $this->db->resultSet();
    }

    public function agregarPersonal($datos)
    {
        $this->db->query("INSERT INTO personal (nombres, apellidos, tipo_documento, numero_documento, genero, fecha_nacimiento, celular, email, direccion_domicilio, departamento, provincia, distrito, perfil, foto) VALUES (:nombres, :apellidos, :tipo_documento, :numero_documento, :genero, :fecha_nacimiento, :celular, :email, :direccion_domicilio, :departamento, :provincia, :distrito, :perfil, :foto)");

        $this->db->bind(':nombres', $datos['nombres']);
        $this->db->bind(':apellidos', $datos['apellidos']);
        $this->db->bind(':tipo_documento', $datos['tipo_documento']);
        $this->db->bind(':numero_documento', $datos['numero_documento']);
        $this->db->bind(':genero', $datos['genero']);
        $this->db->bind(':fecha_nacimiento', $datos['fecha_nacimiento']);
        $this->db->bind(':celular', $datos['celular']);
        $this->db->bind(':email', $datos['email']);
        $this->db->bind(':direccion_domicilio', $datos['direccion_domicilio']);
        $this->db->bind(':departamento', $datos['departamento']);
        $this->db->bind(':provincia', $datos['provincia']);
        $this->db->bind(':distrito', $datos['distrito']);
        $this->db->bind(':perfil', $datos['perfil']);
        $this->db->bind(':foto', $datos['foto']);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function actualizarPersonal($datos)
    {
        $this->db->query("UPDATE personal SET nombres = :nombres, apellidos = :apellidos, tipo_documento = :tipo_documento, numero_documento = :numero_documento, genero = :genero, fecha_nacimiento = :fecha_nacimiento, celular = :celular, email = :email, direccion_domicilio = :direccion_domicilio, departamento = :departamento, provincia = :provincia, distrito = :distrito, perfil = :perfil, foto = :foto WHERE id = :id");

        $this->db->bind(':id', $datos['id']);
        $this->db->bind(':nombres', $datos['nombres']);
        $this->db->bind(':apellidos', $datos['apellidos']);
        $this->db->bind(':tipo_documento', $datos['tipo_documento']);
        $this->db->bind(':numero_documento', $datos['numero_documento']);
        $this->db->bind(':genero', $datos['genero']);
        $this->db->bind(':fecha_nacimiento', $datos['fecha_nacimiento']);
        $this->db->bind(':celular', $datos['celular']);
        $this->db->bind(':email', $datos['email']);
        $this->db->bind(':direccion_domicilio', $datos['direccion_domicilio']);
        $this->db->bind(':departamento', $datos['departamento']);
        $this->db->bind(':provincia', $datos['provincia']);
        $this->db->bind(':distrito', $datos['distrito']);
        $this->db->bind(':perfil', $datos['perfil']);
        $this->db->bind(':foto', $datos['foto']);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function eliminarPersonal($id)
    {
        $this->db->query("DELETE FROM personal WHERE id = :id");
        $this->db->bind(':id', $id);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function obtenerPersonalPorId($id)
    {
        $this->db->query("SELECT * FROM personal WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function cambiarEstado($id, $estado)
    {
        $this->db->query("UPDATE personal SET estado = :estado WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':estado', $estado);
        return $this->db->execute();
    }

    public function obtenerDepartamentos()
    {
        $this->db->query("SELECT * FROM departamentos ORDER BY nombre ASC");
        return $this->db->resultSet();
    }
}
