<?php
session_start();
if(!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once 'conexao.php';

// Resposta em JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['usuario']['id'];
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $apelido = $_POST['apelido'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    
    // Validação básica
    if (empty($nome) || empty($email) || empty($apelido)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, preencha todos os campos obrigatórios.'
        ]);
        exit();
    }
    
    // Verificar se email já existe (exceto o do usuário atual)
    $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Este email já está em uso por outro usuário.'
        ]);
        exit();
    }
    
    // Verificar se apelido já existe (exceto o do usuário atual)
    $stmt = $conexao->prepare("SELECT id FROM usuarios WHERE apelido = ? AND id != ?");
    $stmt->bind_param("si", $apelido, $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Este apelido já está em uso por outro usuário.'
        ]);
        exit();
    }
    
    // Atualizar dados do usuário
    $stmt = $conexao->prepare("UPDATE usuarios SET nome = ?, email = ?, apelido = ?, descricao = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $nome, $email, $apelido, $descricao, $usuario_id);
    
    if ($stmt->execute()) {
        // Atualizar sessão
        $_SESSION['usuario']['nome'] = $nome;
        $_SESSION['usuario']['email'] = $email;
        $_SESSION['usuario']['apelido'] = $apelido;
        
        echo json_encode([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar perfil. Tente novamente.'
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