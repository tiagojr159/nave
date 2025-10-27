<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario']['id'];

// 🔹 Busca a posição da nave salva no banco
$sql = "SELECT posicao_x, posicao_y FROM naves WHERE usuario_id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $nave = $result->fetch_assoc();
    $posInicialX = $nave['posicao_x'];
    $posInicialY = $nave['posicao_y'];
} else {
    // caso o jogador ainda não tenha posição salva
    $posInicialX = 0;
    $posInicialY = 0;
}
?>
