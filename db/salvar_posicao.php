<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php'; 

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo json_encode(["erro" => "Usuário não autenticado"]);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];
$pos_x = intval($_POST['posicao_x']) ? intval($_POST['posicao_x']) : 0;
$pos_y = intval($_POST['posicao_y']) ? intval($_POST['posicao_y']) : 0;

$sql = "UPDATE naves SET posicao_x = ?, posicao_y = ? WHERE usuario_id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("iii", $pos_x, $pos_y, $usuario_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(500);
    echo json_encode(["erro" => "Falha ao salvar posição"]);
}
