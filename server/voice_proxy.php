<?php
// Proxy HTTPS para servidor de voz interno
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Cache-Control: no-cache");

$host = "127.0.0.1";
$port = 8090; // Porta interna do Ratchet
$timeout = 3;

// Conecta localmente ao servidor de voz
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
if (!$socket) {
    echo json_encode(["error" => "Servidor de voz offline: $errstr"]);
    exit;
}

// Recebe áudio codificado e repassa
$input = file_get_contents("php://input");
fwrite($socket, $input);
$response = stream_get_contents($socket);
fclose($socket);

echo $response ?: json_encode(["status" => "ok"]);
?>
