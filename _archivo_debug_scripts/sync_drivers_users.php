<?php
// Script de Sincronización Masiva Personal -> Usuarios
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_transportes');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

echo "Iniciando sincronización masiva...\n";

// 1. Obtener IDs de choferes asignados que faltan en usuarios
$sql_missing = "SELECT DISTINCT ab.chofer_id, p.nombres, p.apellidos, p.email 
                FROM asignaciones_buses ab 
                INNER JOIN personal p ON ab.chofer_id = p.id
                LEFT JOIN usuarios u ON ab.chofer_id = u.id
                WHERE u.id IS NULL AND ab.estado = 1";

$res = $conn->query($sql_missing);

if ($res->num_rows > 0) {
    echo "Se encontraron " . $res->num_rows . " choferes sin usuario de sistema.\n";

    while ($row = $res->fetch_assoc()) {
        $id = $row['chofer_id'];
        $nombres = $conn->real_escape_string($row['nombres']);
        $apellidos = $conn->real_escape_string($row['apellidos']);

        // Generar email dummy sin duplicar si ya existe
        $email_base = strtolower(str_replace(' ', '.', $nombres)) . "." . $id . "@sistema.com";
        $email = $row['email'] ? $row['email'] : $email_base;

        // Password dummy (hash de '123456')
        $pass_hash = '$2y$10$rQ0MnvbbpLaZigRr/YV9wev7WoNpzhExXjEnSkKqUMJuieQ7mFu8C';

        // Insertar en USUARIOS forzando el mismo ID
        $sql_insert = "INSERT INTO usuarios (id, nombres, apellidos, email, password, username, rol_id, estado, created_at) 
                       VALUES ($id, '$nombres', '$apellidos', '$email', '$pass_hash', 'chofer_$id', 3, 'activo', NOW())";

        // Desactivar FK check temporalmente
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        if ($conn->query($sql_insert) === TRUE) {
            echo " [OK] Usuario creado para chofer: $nombres $apellidos (ID: $id)\n";
        } else {
            // Si falla por email duplicado, intentamos con otro
            if (strpos($conn->error, 'Duplicate entry') !== false) {
                $email_alt = "user" . $id . "_" . time() . "@sistema.com";
                $sql_retry = "INSERT INTO usuarios (id, nombres, apellidos, email, password, username, rol_id, estado, created_at) 
                               VALUES ($id, '$nombres', '$apellidos', '$email_alt', '$pass_hash', 'chofer_$id', 3, 'activo', NOW())";
                if ($conn->query($sql_retry) === TRUE) {
                    echo " [OK] Usuario creado (con email alternativo) para: $nombres (ID: $id)\n";
                } else {
                    echo " [ERROR] Falló reintento para ID $id: " . $conn->error . "\n";
                }
            } else {
                echo " [ERROR] No se pudo crear usuario para ID $id: " . $conn->error . "\n";
            }
        }
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
    }
} else {
    echo "Todo en orden. No faltan usuarios para los choferes asignados activamente.\n";
}

$conn->close();
echo "Sincronización finalizada.";
