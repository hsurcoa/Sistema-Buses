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

    // =====================================================================
    //  Gestion de usuarios del sistema (Administrador > Usuarios del sistema)
    // =====================================================================

    /**
     * Usuarios con su rol y cuanto historial tienen (boletos vendidos, cajas,
     * encomiendas): con historial no se pueden borrar, solo desactivar.
     * "automatica" marca las cuentas creadas por el sistema para choferes
     * (chofer_18, *.sistema.temp...) que hoy ya no son necesarias.
     */
    public function listarUsuarios()
    {
        $this->db->query("SELECT u.id, u.username, u.nombres, u.apellidos, u.email, u.rol_id, u.estado, u.created_at,
                                 u.nro_documento, u.celular, u.sucursal_id, t.nombre_sede AS sucursal,
                                 r.nombre AS rol,
                                 (SELECT COUNT(*) FROM boletos b WHERE b.usuario_vendedor_id = u.id) AS total_boletos,
                                 (SELECT COUNT(*) FROM cajas_sesiones c WHERE c.usuario_id = u.id) AS total_cajas,
                                 (SELECT COUNT(*) FROM encomiendas e WHERE e.usuario_creacion_id = u.id) AS total_encomiendas,
                                 (u.username LIKE 'chofer\\_%' OR u.email LIKE '%.sistema.temp' OR u.email LIKE '%@test.com') AS automatica
                          FROM usuarios u
                          LEFT JOIN roles r ON r.id = u.rol_id
                          LEFT JOIN terminales t ON t.id = u.sucursal_id
                          ORDER BY u.estado = 'activo' DESC, r.nombre, u.apellidos, u.nombres");
        return $this->db->resultSet();
    }

    public function obtenerUsuario($id)
    {
        $this->db->query("SELECT u.id, u.username, u.nombres, u.apellidos, u.email, u.rol_id, u.estado,
                                 u.nro_documento, u.celular, u.sucursal_id, r.nombre AS rol
                          FROM usuarios u LEFT JOIN roles r ON r.id = u.rol_id
                          WHERE u.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single() ?: null;
    }

    public function listarRolesActivos()
    {
        $this->db->query("SELECT id, nombre, descripcion FROM roles WHERE activo = 1 ORDER BY nombre");
        return $this->db->resultSet();
    }

    public function rolActivo($rolId)
    {
        $this->db->query("SELECT id, nombre FROM roles WHERE id = :id AND activo = 1");
        $this->db->bind(':id', $rolId);
        return $this->db->single() ?: null;
    }

    public function campoEnUso($campo, $valor, $excluirId = 0)
    {
        $columna = $campo === 'username' ? 'username' : 'email';
        $this->db->query("SELECT id FROM usuarios WHERE {$columna} = :valor AND id <> :id LIMIT 1");
        $this->db->bind(':valor', $valor);
        $this->db->bind(':id', (int) $excluirId);
        return (bool) $this->db->single();
    }

    /** Administradores activos, sin contar opcionalmente a un usuario. */
    public function contarAdministradoresActivos($excluirId = 0)
    {
        $this->db->query("SELECT COUNT(*) AS total FROM usuarios u JOIN roles r ON r.id = u.rol_id
                          WHERE r.nombre = 'Administrador' AND u.estado = 'activo' AND u.id <> :id");
        $this->db->bind(':id', (int) $excluirId);
        return (int) $this->db->single()->total;
    }

    public function crearUsuario($d)
    {
        $this->db->query("INSERT INTO usuarios (username, nombres, apellidos, email, password, rol_id, estado, nro_documento, celular, sucursal_id)
                          VALUES (:username, :nombres, :apellidos, :email, :password, :rol_id, :estado, :doc, :celular, :sucursal)");
        $this->bindUsuario($d);
        $this->db->bind(':password', $d['password_hash']);
        $this->db->execute();
        return (int) $this->db->lastInsertId();
    }

    public function actualizarUsuario($d)
    {
        $conPassword = !empty($d['password_hash']);
        $this->db->query("UPDATE usuarios SET username = :username, nombres = :nombres, apellidos = :apellidos, email = :email,
                                 rol_id = :rol_id, estado = :estado, nro_documento = :doc, celular = :celular, sucursal_id = :sucursal"
            . ($conPassword ? ', password = :password' : '') . "
                          WHERE id = :id");
        $this->bindUsuario($d);
        if ($conPassword) {
            $this->db->bind(':password', $d['password_hash']);
        }
        $this->db->bind(':id', $d['id']);
        return $this->db->execute();
    }

    public function cambiarEstadoUsuario($id, $activo)
    {
        $this->db->query("UPDATE usuarios SET estado = :estado WHERE id = :id");
        $this->db->bind(':estado', $activo ? 'activo' : 'inactivo');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /** Borra la cuenta solo si no tiene historial (si lo tiene, la FK lo impediria igual). */
    public function eliminarUsuarioSinHistorial($id)
    {
        $this->db->query("DELETE FROM usuarios WHERE id = :id
                            AND NOT EXISTS (SELECT 1 FROM boletos WHERE usuario_vendedor_id = :id1)
                            AND NOT EXISTS (SELECT 1 FROM cajas_sesiones WHERE usuario_id = :id2)
                            AND NOT EXISTS (SELECT 1 FROM encomiendas WHERE usuario_creacion_id = :id3)");
        $this->db->bind(':id', $id);
        $this->db->bind(':id1', $id);
        $this->db->bind(':id2', $id);
        $this->db->bind(':id3', $id);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function estaActivo($id)
    {
        $this->db->query("SELECT estado FROM usuarios WHERE id = :id");
        $this->db->bind(':id', $id);
        $u = $this->db->single();
        return $u && $u->estado === 'activo';
    }

    private function bindUsuario($d)
    {
        $this->db->bind(':username', $d['username'] !== '' ? $d['username'] : null);
        $this->db->bind(':nombres', $d['nombres']);
        $this->db->bind(':apellidos', $d['apellidos']);
        $this->db->bind(':email', $d['email']);
        $this->db->bind(':rol_id', $d['rol_id']);
        $this->db->bind(':estado', $d['activo'] ? 'activo' : 'inactivo');
        $this->db->bind(':doc', $d['nro_documento'] !== '' ? $d['nro_documento'] : null);
        $this->db->bind(':celular', $d['celular'] !== '' ? $d['celular'] : null);
        $this->db->bind(':sucursal', !empty($d['sucursal_id']) ? (int) $d['sucursal_id'] : null);
    }
}
