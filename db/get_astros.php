<?php
header('Content-Type: application/json');
require_once 'conexao.php';

if ($conexao->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha na conexão: ' . $conexao->connect_error]);
    exit;
}

$sql = "SELECT id, nome, posicao_x, posicao_y, tipo, img, tamanho, cor, descricao, recursos 
        FROM astros ORDER BY id ASC";
$res = $conexao->query($sql);

$astros = [];

while ($row = $res->fetch_assoc()) {
    $astros[] = [
        'id' => (int)$row['id'],
        'name' => $row['nome'],
        'x' => (float)$row['posicao_x'],
        'y' => (float)$row['posicao_y'],
        'r' => (int)$row['tamanho'] * 3,
        'cor' => $row['cor'],
        'descricao' => $row['descricao'],
        'recursos' => $row['recursos'],
        'tipo' => $row['tipo'],
        'img' => $row['img'], // ⚠️ usa exatamente o nome da imagem do banco
        'rotation' => 0,
        'rotationSpeed' => 0.002
    ];
}

echo json_encode($astros);
?>
