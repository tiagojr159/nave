<?php
// db/get_naves_online.php
require_once '../db/conexao.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $idAtual = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // 🔹 Verifica se a conexão MySQLi está ativa
    if (!isset($conexao) || !$conexao instanceof mysqli) {
        throw new Exception("Conexão com o banco de dados não foi inicializada.");
    }

    // 🔹 Consulta: naves de outros jogadores
    $sql = "
        SELECT
            u.id AS usuario_id,
            u.nome AS nome_usuario,
            n.id AS id_nave,
            n.nome_nave AS nome_nave,
            CAST(n.posicao_x AS DECIMAL(10,2)) AS x,
            CAST(n.posicao_y AS DECIMAL(10,2)) AS y,
            n.energia
        FROM
            usuarios u
        INNER JOIN
            naves n ON u.id = n.usuario_id
        WHERE
            u.id != ?
           
    ";

    $stmt = $conexao->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar consulta: " . $conexao->error);
    }

    $stmt->bind_param("i", $idAtual);
    $stmt->execute();
    $result = $stmt->get_result();

    // 🔹 Inicializa array de naves
    $naves = [];

    while ($row = $result->fetch_assoc()) {
        $naves[] = [
            'id'       => (int)$row['id_nave'],
            'nome'     => $row['nome_usuario'],
            'x'        => (float)$row['x'],
            'y'        => (float)$row['y'],
            'energia'  => (float)$row['energia']
        ];
    }

    // 🔹 Retorna sempre JSON válido
    echo json_encode($naves, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    echo json_encode([
        'error'   => true,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
