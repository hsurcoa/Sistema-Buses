<?php

class UsuarioModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Verificar si el email ya existe en la tabla usuarios
    // Verificar si el email ya existe en la tabla PERSONAL (para prevenir duplicados globales)
    public function existeEmail($email, $id = null)
    {
        if ($id) {
            // Modo Edición: Verificar si existe otro usuario con ese email (excluyendo al actual)
            $this->db->query("SELECT COUNT(*) as total FROM personal WHERE email = :email AND id != :id");
            $this->db->bind(':id', $id);
        } else {
            // Modo Creación
            $this->db->query("SELECT COUNT(*) as total FROM personal WHERE email = :email");
        }

        $this->db->bind(':email', $email);
        $row = $this->db->single();

        return $row->total > 0;
    }

    // Registrar nuevo usuario desde el módulo de Personal
    // Se asume que personal_id es opcional o se agregará si existe la relación
    public function registrarUsuario($datos)
    {
        // Obtener ID del rol dinámicamente desde la base de datos
        // Se asume que personal 'perfil' es el NAME del rol en tabla roles
        $this->db->query("SELECT id FROM roles WHERE nombre = :nombre LIMIT 1");
        $this->db->bind(':nombre', $datos['perfil']);
        $rolData = $this->db->single();

        $rol_id = $rolData ? $rolData->id : 2; // Default 2 (Usuario) si no se encuentra

        $this->db->query("INSERT INTO usuarios (username, nombres, apellidos, email, password, rol_id, estado) VALUES (:username, :nombres, :apellidos, :email, :password, :rol_id, 1)");

        // 'username' generated in controller
        $this->db->bind(':username', $datos['username']);
        $this->db->bind(':nombres', $datos['nombres']);
        $this->db->bind(':apellidos', $datos['apellidos']);
        $this->db->bind(':email', $datos['email']);
        $this->db->bind(':password', $datos['password_hash']);
        $this->db->bind(':rol_id', $rol_id);

        if ($this->db->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function actualizarCredenciales($oldEmail, $newEmail, $newPasswordHash)
    {
        // Actualizamos email y password donde coincida el email antiguo
        $this->db->query("UPDATE usuarios SET email = :newEmail, password = :newPass WHERE email = :oldEmail");
        $this->db->bind(':newEmail', $newEmail);
        $this->db->bind(':newPass', $newPasswordHash);
        $this->db->bind(':oldEmail', $oldEmail);

        return $this->db->execute();
    }
}
