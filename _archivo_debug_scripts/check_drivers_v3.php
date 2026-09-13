<?php
// Script de Diagnóstico de Choferes
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_transportes');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

echo "=== REPORTE DE ESTADO DE FLOTA Y CHOFERES ===\n\n";

$sql = "SELECT b.id as bus_id, b.placa, b.numero_interno 
        FROM buses b 
        WHERE b.estado = 'activo'";
$res_buses = $conn->query($sql);

while ($bus = $res_buses->fetch_assoc()) {
    echo "BUS: {$bus['placa']} (Interno: {$bus['numero_interno']})\n";

    // Buscar asignación
    $sql_assign = "SELECT chofer_id, copiloto_id FROM asignaciones_buses WHERE bus_id = {$bus['bus_id']} AND estado = 1";
    $res_assign = $conn->query($sql_assign);

    if ($res_assign->num_rows > 0) {
        $assign = $res_assign->fetch_assoc();
        $chofer_id = $assign['chofer_id'];
        echo "  -> Tiene asignación activa. ID Chofer: $chofer_id\n";

        // Verificar en tabla PERSONAL (FK Real)
        $res_p = $conn->query("SELECT nombres, apellidos FROM personal WHERE id = $chofer_id");
        $exists_personal = ($res_p->num_rows > 0);
        $name_personal = $exists_personal ? $res_p->fetch_assoc() : null;

        // Verificar en tabla USUARIOS (Join del Codigo)
        $res_u = $conn->query("SELECT nombres, apellidos FROM usuarios WHERE id = $chofer_id");
        $exists_usuario = ($res_u->num_rows > 0);
        $name_usuario = $exists_usuario ? $res_u->fetch_assoc() : null;

        echo "  -> Status Tabla PERSONAL: " . ($exists_personal ? "OK ({$name_personal['nombres']})" : "FALTANTE") . "\n";
        echo "  -> Status Tabla USUARIOS: " . ($exists_usuario ? "OK ({$name_usuario['nombres']})" : "FALTANTE (Causa del error visual)") . "\n";

        if ($exists_personal && !$exists_usuario) {
            echo "  [!] DIAGNÓSTICO: El chofer existe en la nómina (Personal) pero no tiene usuario de sistema. El código exige usuario.\n";
            // Auto-fix proposal could go here
        }
    } else {
        echo "  -> [ALERTA] No tiene chofer asignado (Tabla asignaciones_buses vacía o inactiva).\n";
    }
    echo "------------------------------------------------\n";
}

$conn->close();
