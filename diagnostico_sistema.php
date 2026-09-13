<?php
// diagnostico_sistema.php
// Script de Diagnóstico Exhaustivo para Venta de Pasajes
// Ejecutar desde línea de comandos o navegador

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_transportes');

echo "=== INICIO DIAGNÓSTICO ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";

// 1. Verificación de Conexión
echo "\n[1] Verificando Conexión a Base de Datos...\n";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "OK: Conexión exitosa a " . DB_NAME . "\n";
} catch (PDOException $e) {
    die("FATAL: Error de conexión: " . $e->getMessage() . "\n");
}

// 2. Verificación de Tablas Críticas
echo "\n[2] Verificando Existencia de Tablas...\n";
$tablasEsperadas = ['usuarios', 'clientes', 'rutas', 'viajes', 'boletos', 'buses', 'sesiones_caja'];
$tablasEncontradas = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tablasEncontradas[] = $row[0];
}

foreach ($tablasEsperadas as $tabla) {
    if (in_array($tabla, $tablasEncontradas)) {
        echo "OK: Tabla '$tabla' existe.\n";
    } else {
        echo "ERROR: Tabla '$tabla' NO ENCONTRADA.\n";
    }
}

// 3. Análisis de Estructura de Tabla 'boletos'
echo "\n[3] Analizando estructura de 'boletos'...\n";
if (in_array('boletos', $tablasEncontradas)) {
    $stmt = $pdo->query("DESCRIBE boletos");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fields as $field) {
        echo " - Campo: {$field['Field']} ({$field['Type']}) Null:{$field['Null']} Key:{$field['Key']}\n";
    }
}

// 4. Prueba de Lógica de Inserción (Simulada)
echo "\n[4] Prueba de Inserción Simulada (Rollback al final)...\n";
try {
    $pdo->beginTransaction();

    // Buscar viaje
    $stmt = $pdo->query("SELECT id FROM viajes LIMIT 1");
    $viaje = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$viaje) throw new Exception("No hay viajes registrados para probar.");
    $viajeId = $viaje['id'];
    echo "Usando Viaje ID: $viajeId\n";

    // Buscar o Insertar Cliente
    echo "Intentando insertar cliente de prueba...\n";
    $stmt = $pdo->prepare("INSERT INTO clientes (nombres, apellidos, numero_documento, tipo_documento) VALUES ('Test', 'User', '9999999', 'CI')");
    $stmt->execute();
    $clienteId = $pdo->lastInsertId();
    echo "Cliente insertado ID: $clienteId\n";

    // Verificar Sesion Caja (Punto Crítico)
    $sesionId = 1; // Default usado en el código
    if (in_array('sesiones_caja', $tablasEncontradas)) {
        $stmt = $pdo->query("SELECT id FROM sesiones_caja WHERE id = 1");
        if (!$stmt->fetch()) {
            echo "ADVERTENCIA: Sesión de caja ID 1 no existe. Creándola para la prueba...\n";
            $usuarioId = 1; // Asumiendo usuario 1
            // Verificar usuario 1
            $u = $pdo->query("SELECT id FROM usuarios WHERE id=1")->fetch();
            if (!$u) {
                echo "Creando usuario default id 1...\n";
                $pdo->query("INSERT INTO usuarios (id, nombres, usuario, password, rol) VALUES (1, 'Admin', 'admin', '123', 'admin')");
            }
            $pdo->query("INSERT INTO sesiones_caja (id, usuario_id, fecha_apertura, estado, monto_inicial) VALUES (1, 1, NOW(), 'abierta', 0)");
            echo "Sesión de caja creada.\n";
        }
    } else {
        echo "ERROR CRÍTICO: El código usa 'sesion_caja_id' pero la tabla no existe.\n";
    }

    // Insertar Boleto
    echo "Intentando insertar boleto...\n";
    $sql = "INSERT INTO boletos (viaje_id, cliente_id, usuario_vendedor_id, numero_asiento, precio_final, estado, fecha_reserva, codigo_boleto, sesion_caja_id) 
            VALUES (:vid, :cid, 1, 999, 100, 'reservado', NOW(), 'TEST-001', :sesion)";

    $stmt = $pdo->prepare($sql);
    $params = [
        ':vid' => $viajeId,
        ':cid' => $clienteId,
        ':sesion' => 1
    ];

    // Si la tabla boletos NO tiene sesion_caja_id, esto fallará y nos dirá el error
    $stmt->execute($params);
    echo "OK: Boleto insertado correctamente (ID: " . $pdo->lastInsertId() . ")\n";

    $pdo->rollBack();
    echo "Prueba finalizada (Cambios revertidos).\n";
} catch (Exception $e) {
    echo "FALLO EN PRUEBA: " . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) $pdo->rollBack();
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
