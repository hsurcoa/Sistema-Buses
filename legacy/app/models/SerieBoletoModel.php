<?php
class SerieBoletoModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Métodos para Dropdowns
    public function obtenerVendedores()
    {
        // Selecciona id y CONCAT(nombres, ' ', apellidos) de la tabla personal donde el rol sea 'Vendedor'
        // Nota: En la BD la columna es 'perfil', no 'rol'.
        $this->db->query("SELECT id, CONCAT(nombres, ' ', apellidos) as nombre_completo FROM personal WHERE perfil = 'Vendedor' AND estado = 1");
        return $this->db->resultSet();
    }

    public function obtenerSedes()
    {
        // Selecciona id y nombre_sede de la tabla sedes (terminales en nuestro caso)
        $this->db->query("SELECT id, nombre_sede FROM terminales WHERE estado = 1");
        return $this->db->resultSet();
    }

    // Método para Listar (Tabla)
    public function listarSeries()
    {
        // INNER JOIN con personal y sedes
        // Devolver: Nombre de la Sede, Nombre completo del Vendedor, Número de Serie y Estado.
        $this->db->query("
            SELECT 
                s.id,
                t.nombre_sede, 
                CONCAT(p.nombres, ' ', p.apellidos) as vendedor_nombre, 
                s.numero_serie, 
                s.estado,
                s.usuario_id, 
                s.sede_id
            FROM series_boletos s 
            INNER JOIN personal p ON s.usuario_id = p.id 
            INNER JOIN terminales t ON s.sede_id = t.id 
            ORDER BY s.id DESC
        ");
        return $this->db->resultSet();
    }

    public function obtenerSerie($id)
    {
        $this->db->query("SELECT * FROM series_boletos WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Método Guardar
    public function registrarSerie($datos)
    {
        if (!empty($datos['id'])) {
            // Update
            $this->db->query("UPDATE series_boletos SET usuario_id = :usuario_id, sede_id = :sede_id, numero_serie = :numero_serie WHERE id = :id");
            $this->db->bind(':id', $datos['id']);
            $this->db->bind(':usuario_id', $datos['usuario_id']);
            $this->db->bind(':sede_id', $datos['sede_id']);
            $this->db->bind(':numero_serie', $datos['numero_serie']);
        } else {
            // Insert
            $this->db->query("INSERT INTO series_boletos (usuario_id, sede_id, numero_serie, estado) VALUES (:usuario_id, :sede_id, :numero_serie, :estado)");
            $this->db->bind(':usuario_id', $datos['usuario_id']);
            $this->db->bind(':sede_id', $datos['sede_id']);
            $this->db->bind(':numero_serie', $datos['numero_serie']);
            $this->db->bind(':estado', 1);
        }

        return $this->db->execute();
    }

    public function cambiarEstado($id, $estado)
    {
        $this->db->query("UPDATE series_boletos SET estado = :estado WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->bind(':estado', $estado);
        return $this->db->execute();
    }
}
