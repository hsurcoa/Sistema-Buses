<?php
// test_transaction_endpoint.php
// Script to simulate a cURL POST request to the local controller

$url = 'http://localhost/venta-pasajes/controladortransacciones/index';

// Payload derived from user context
$data = [
    'accion' => 'nueva_transaccion',
    'viaje_id' => 2, // From logs
    'asiento' => 5,  // From logs (occupied)
    'documento' => '1234567',
    'nombres' => 'Test',
    'apellidos' => 'User',
    'celular' => '12345678',
    'precio' => 40,
    'tipo' => 'venta'
];

$payload = json_encode($data);

echo "Sending POST to: $url\n";
echo "Payload: $payload\n";
echo "--------------------------------------------------\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in output

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpcode\n";
if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "Raw Response:\n$response\n";
}
