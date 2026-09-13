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
