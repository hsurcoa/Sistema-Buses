<?php
// debug_model_data.php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

// Mock RutaModel methods
class MockRutaModel
{
    private $db;
    public function __construct()
    {
        $this->db = new Database();
    }
    public function obtenerAsientosOcupados($viajeId)
    {
        try {
            $this->db->query("SELECT id, numero_asiento, estado FROM boletos WHERE viaje_id = :viaje_id AND estado IN ('vendido', 'reservado')");
            $this->db->bind(':viaje_id', $viajeId);
            $resultados = $this->db->resultSet();
            $ocupados = [];
            foreach ($resultados as $fila) {
                $ocupados[] = [
                    'id' => $fila->id,
                    'numero' => (int) $fila->numero_asiento,
                    'estado' => $fila->estado
                ];
            }
            return $ocupados;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}

$model = new MockRutaModel();
$viajeId = 2;
$ocupados = $model->obtenerAsientosOcupados($viajeId);

echo "<h1>Data Debug - Viaje $viajeId</h1>";
echo "<h2>Asientos Ocupados:</h2>";
echo "<pre>";
print_r($ocupados);
echo "</pre>";

// Check specific seat 9
$seat9 = false;
foreach ($ocupados as $o) {
    if ($o['numero'] === 9) {
        $seat9 = $o;
        break;
    }
}

if ($seat9) {
    echo "<h3 style='color:green'>Seat 9 FOUND in DB result. Status: " . $seat9['estado'] . "</h3>";
} else {
    echo "<h3 style='color:red'>Seat 9 NOT FOUND in DB result.</h3>";
}
