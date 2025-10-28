<?php
// verifica_login.php
session_start();
require_once '../db/conexao.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos.']);
        exit();
    }

    $stmt = $conexao->prepare("SELECT id, nome, email, apelido, senha FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if (hash('sha256', $senha) === $usuario['senha']) {
            // 🔹 Login OK → Buscar (ou criar) nave do jogador
            $idUsuario = $usuario['id'];

            $stmtNave = $conexao->prepare("SELECT id FROM naves WHERE usuario_id = ?");
            $stmtNave->bind_param("i", $idUsuario);
            $stmtNave->execute();
            $resNave = $stmtNave->get_result();

            if ($resNave->num_rows > 0) {
                $nave = $resNave->fetch_assoc();
                $idNave = $nave['id'];
            } else {
                // 🔹 Cria nova nave se não existir
                $stmtNova = $conexao->prepare("
                    INSERT INTO naves (usuario_id, nome, tipo, posicao_x, posicao_y, energia, ativo)
                    VALUES (?, ?, 'player', FLOOR(RAND()*80000), FLOOR(RAND()*80000), 100, 1)
                ");
                $stmtNova->bind_param("is", $idUsuario, $usuario['apelido']);
                $stmtNova->execute();
                $idNave = $conexao->insert_id;
            }

            // 🔹 Armazena tudo na sessão
            $_SESSION['usuario'] = [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'apelido' => $usuario['apelido'],
                'id_nave' => $idNave
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Login realizado com sucesso! Redirecionando...',
                'nave_id' => $idNave
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}

$conexao->close();
?>
