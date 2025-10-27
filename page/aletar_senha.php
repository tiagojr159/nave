<?php
session_start();
if(!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once '../db/conexao.php';

// Resposta em JSON
header('Content-Type: application/json');

 $data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['senha'])) {
    $usuario_id = $_SESSION['usuario']['id'];
    $senha = $data['senha'];
    
    // Validação básica
    if (empty($senha) || strlen($senha) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'A senha deve ter pelo menos 6 caracteres.'
        ]);
        exit();
    }
    
    // Hash da senha
    $senha_hash = hash('sha256', $senha);
    
    // Atualizar senha
    $stmt = $conexao->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $stmt->bind_param("si", $senha_hash, $usuario_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Senha alterada com sucesso.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao alterar senha. Tente novamente.'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Requisição inválida.'
    ]);
}

 $conexao->close();
?>