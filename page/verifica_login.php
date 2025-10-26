<?php
session_start();
require_once '../db/conexao.php';

// Resposta em JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Validação básica
    if (empty($email) || empty($senha)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, preencha todos os campos.'
        ]);
        exit();
    }
    
    // Verificar no banco de dados
    $stmt = $conexao->prepare("SELECT id, nome, email, apelido, senha FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        // Verificar senha (hash SHA2-256)
        if (hash('sha256', $senha) === $usuario['senha']) {
            // Login bem-sucedido
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'apelido' => $usuario['apelido']
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Login realizado com sucesso! Redirecionando...'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Senha incorreta.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não encontrado.'
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