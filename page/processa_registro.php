<?php
session_start();
require_once '../db/conexao.php';

// Resposta em JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $apelido = $_POST['apelido'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validação básica
    if (empty($nome) || empty($email) || empty($apelido) || empty($senha)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, preencha todos os campos obrigatórios.'
        ]);
        exit();
    }
    
    if ($senha !== $confirmar_senha) {
        echo json_encode([
            'success' => false,
            'message' => 'As senhas não coincidem.'
        ]);
        exit();
    }
    
    // Verificar se email já existe
    $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Este email já está em uso.'
        ]);
        exit();
    }
    
    // Verificar se apelido já existe
    $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE apelido = ?");
    $stmt->bind_param("s", $apelido);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Este apelido já está em uso.'
        ]);
        exit();
    }
    
    // Hash da senha
    $senha_hash = hash('sha256', $senha);
    
    // Inserir usuário
    $stmt = $conexao->prepare("INSERT INTO usuarios (nome, email, apelido, descricao, senha) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nome, $email, $apelido, $descricao, $senha_hash);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Conta criada com sucesso! Redirecionando para o login...'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar conta. Tente novamente.'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método de requisição inválido.'
    ]);
}

 $conexao->close();
?>