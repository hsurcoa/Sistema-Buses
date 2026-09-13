<?php

/**
 * Modelo: RolPermiso
 * Descripción: Gestión de roles y permisos del sistema
 * Fecha: 2025-12-20
 */

class RolPermiso
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Obtener todos los roles activos
     */
    public function obtenerRoles()
    {
        $this->db->query("SELECT * FROM roles WHERE activo = 1 ORDER BY nombre");
        return $this->db->resultSet();
    }

    /**
     * Obtener un rol por ID
     */
    public function obtenerRolPorId($id)
    {
        $this->db->query("SELECT * FROM roles WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Obtener todos los permisos agrupados
     */
    public function obtenerPermisosAgrupados()
    {
        $this->db->query("
            SELECT 
                id,
                grupo,
                vista,
                clave,
                descripcion,
                orden
            FROM permisos 
            WHERE activo = 1 
            ORDER BY 
                FIELD(grupo, 'Dashboard', 'Procesos', 'Reportes', 'Registros', 'Administrador'),
                orden ASC
        ");

        $permisos = $this->db->resultSet();

        // Agrupar permisos por grupo
        $permisosAgrupados = [];
        foreach ($permisos as $permiso) {
            $permisosAgrupados[$permiso->grupo][] = $permiso;
        }

        return $permisosAgrupados;
    }

    /**
     * Obtener permisos de un rol específico
     */
    public function obtenerPermisosPorRol($rol_id)
    {
        $this->db->query("
            SELECT p.id, p.clave
            FROM permisos p
            INNER JOIN rol_permiso rp ON p.id = rp.permiso_id
            WHERE rp.rol_id = :rol_id AND p.activo = 1
        ");
        $this->db->bind(':rol_id', $rol_id);
        $resultado = $this->db->resultSet();

        // Convertir a array de IDs
        $permisos = [];
        foreach ($resultado as $permiso) {
            $permisos[] = $permiso->id;
        }

        return $permisos;
    }

    /**
     * Actualizar permisos de un rol
     */
    public function actualizarPermisosRol($rol_id, $permisos_ids)
    {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();

            // Eliminar permisos existentes del rol
            $this->db->query("DELETE FROM rol_permiso WHERE rol_id = :rol_id");
            $this->db->bind(':rol_id', $rol_id);
            $this->db->execute();

            // Insertar nuevos permisos
            if (!empty($permisos_ids)) {
                foreach ($permisos_ids as $permiso_id) {
                    $this->db->query("
                        INSERT INTO rol_permiso (rol_id, permiso_id) 
                        VALUES (:rol_id, :permiso_id)
                    ");
                    $this->db->bind(':rol_id', $rol_id);
                    $this->db->bind(':permiso_id', $permiso_id);
                    $this->db->execute();
                }
            }

            // Confirmar transacción
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollBack();
            error_log("Error al actualizar permisos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear un nuevo rol
     */
    public function crearRol($datos)
    {
        $this->db->query("
            INSERT INTO roles (nombre, descripcion, activo) 
            VALUES (:nombre, :descripcion, :activo)
        ");

        $this->db->bind(':nombre', $datos['nombre']);
        $this->db->bind(':descripcion', $datos['descripcion'] ?? '');
        $this->db->bind(':activo', $datos['activo'] ?? 1);

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Actualizar un rol
     */
    public function actualizarRol($id, $datos)
    {
        $this->db->query("
            UPDATE roles 
            SET nombre = :nombre, 
                descripcion = :descripcion, 
                activo = :activo 
            WHERE id = :id
        ");

        $this->db->bind(':id', $id);
        $this->db->bind(':nombre', $datos['nombre']);
        $this->db->bind(':descripcion', $datos['descripcion'] ?? '');
        $this->db->bind(':activo', $datos['activo'] ?? 1);

        return $this->db->execute();
    }

    /**
     * Eliminar un rol (soft delete)
     */
    public function eliminarRol($id)
    {
        $this->db->query("UPDATE roles SET activo = 0 WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Verificar si un usuario tiene un permiso específico
     */
    public function tienePermiso($usuario_id, $clave_permiso)
    {
        $this->db->query("
            SELECT COUNT(*) as tiene_permiso
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            INNER JOIN rol_permiso rp ON r.id = rp.rol_id
            INNER JOIN permisos p ON rp.permiso_id = p.id
            WHERE u.id = :usuario_id 
            AND p.clave = :clave_permiso
            AND u.activo = 1 
            AND r.activo = 1 
            AND p.activo = 1
        ");

        $this->db->bind(':usuario_id', $usuario_id);
        $this->db->bind(':clave_permiso', $clave_permiso);

        $resultado = $this->db->single();
        return $resultado->tiene_permiso > 0;
    }

    /**
     * Obtener resumen de roles con cantidad de permisos
     */
    public function obtenerResumenRoles()
    {
        $this->db->query("SELECT * FROM v_resumen_roles");
        return $this->db->resultSet();
    }
}
