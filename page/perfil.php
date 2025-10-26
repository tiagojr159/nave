<?php
session_start();
if(!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once 'conexao.php';

// Buscar dados do usuário
 $usuario_id = $_SESSION['usuario']['id'];
 $stmt = $conexao->prepare("SELECT nome, email, apelido, descricao FROM usuarios WHERE id = ?");
 $stmt->bind_param("i", $usuario_id);
 $stmt->execute();
 $resultado = $stmt->get_result();
 $usuario = $resultado->fetch_assoc();

// Buscar naves do usuário
 $stmt = $conexao->prepare("SELECT nome_nave, posicao_x, posicao_y, velocidade, direcao, energia, escudo FROM naves WHERE usuario_id = ?");
 $stmt->bind_param("i", $usuario_id);
 $stmt->execute();
 $naves = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Sistema Espacial</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .star {
            position: absolute;
            background: #fff;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.2; }
            50% { opacity: 1; }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card h2 {
            color: #00d2ff;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .profile-info {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00d2ff, #3a7bd5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-right: 30px;
            box-shadow: 0 0 30px rgba(0, 210, 255, 0.5);
        }

        .user-details h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .user-details p {
            color: #aaa;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 0.9rem;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #00d2ff;
            box-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            color: #fff;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 210, 255, 0.3);
        }

        .naves-list {
            margin-top: 20px;
        }

        .naves-list h3 {
            color: #00d2ff;
            margin-bottom: 15px;
        }

        .nave-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #00d2ff;
        }

        .nave-item h4 {
            margin-bottom: 10px;
            color: #fff;
        }

        .nave-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .stat {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #aaa;
        }

        .stat-value {
            font-weight: bold;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            border-radius: 4px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #00d2ff;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.2);
            border-left: 4px solid #4caf50;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .space-object {
            position: absolute;
            border-radius: 50%;
            opacity: 0.3;
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .planet1 {
            width: 80px;
            height: 80px;
            background: radial-gradient(circle, #4169E1, #1E90FF);
            top: 10%;
            left: 5%;
            animation-duration: 25s;
        }

        .planet2 {
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, #FF6347, #CD5C5C);
            bottom: 15%;
            right: 5%;
            animation-duration: 30s;
        }

        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .profile-info {
                flex-direction: column;
                text-align: center;
            }
            
            .avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Animação de fundo com estrelas -->
    <div class="stars" id="stars"></div>
    
    <!-- Objetos espaciais flutuantes -->
    <div class="space-object planet1"></div>
    <div class="space-object planet2"></div>
    
    <div class="container">
        <a href="mapa_astrados.php" class="back-link">← Voltar ao Mapa</a>
        
        <header>
            <h1>👨‍🚀 Meu Perfil</h1>
            <p>Gerencie suas informações e frota espacial</p>
        </header>
        
        <div class="success-message" id="successMessage">
            Perfil atualizado com sucesso!
        </div>
        
        <div class="profile-content">
            <div class="card">
                <h2>Informações Pessoais</h2>
                
                <div class="profile-info">
                    <div class="avatar">
                        <?php echo strtoupper(substr($usuario['apelido'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($usuario['nome']); ?></h3>
                        <p>@<?php echo htmlspecialchars($usuario['apelido']); ?></p>
                    </div>
                </div>
                
                <form id="profileForm">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="apelido">Apelido</label>
                        <input type="text" id="apelido" name="apelido" value="<?php echo htmlspecialchars($usuario['apelido']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($usuario['descricao']); ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <button type="button" class="btn btn-secondary" id="changePasswordBtn">Alterar Senha</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <h2>Minha Frota Espacial</h2>
                
                <?php if ($naves->num_rows > 0): ?>
                    <div class="naves-list">
                        <?php while ($nave = $naves->fetch_assoc()): ?>
                            <div class="nave-item">
                                <h4><?php echo htmlspecialchars($nave['nome_nave']); ?></h4>
                                
                                <div class="nave-stats">
                                    <div class="stat">
                                        <span class="stat-label">Posição X:</span>
                                        <span class="stat-value"><?php echo $nave['posicao_x']; ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Posição Y:</span>
                                        <span class="stat-value"><?php echo $nave['posicao_y']; ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Velocidade:</span>
                                        <span class="stat-value"><?php echo $nave['velocidade']; ?> km/s</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Direção:</span>
                                        <span class="stat-value"><?php echo $nave['direcao']; ?></span>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span>Energia:</span>
                                        <span><?php echo $nave['energia']; ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $nave['energia']; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 10px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <span>Escudo:</span>
                                        <span><?php echo $nave['escudo']; ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $nave['escudo']; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #aaa; margin-top: 30px;">
                        Você ainda não possui naves. <a href="mapa_astrados.php" style="color: #00d2ff;">Crie sua primeira nave!</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Gerar estrelas aleatórias
        function createStars() {
            const starsContainer = document.getElementById('stars');
            const starCount = 100;
            
            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.classList.add('star');
                
                // Tamanho aleatório
                const size = Math.random() * 3;
                star.style.width = `${size}px`;
                star.style.height = `${size}px`;
                
                // Posição aleatória
                star.style.left = `${Math.random() * 100}%`;
                star.style.top = `${Math.random() * 100}%`;
                
                // Atraso na animação
                star.style.animationDelay = `${Math.random() * 3}s`;
                
                starsContainer.appendChild(star);
            }
        }
        
        // Inicializar estrelas
        createStars();
        
        // Processar formulário de perfil
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const successMessage = document.getElementById('successMessage');
            
            fetch('atualizar_perfil.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.style.display = 'block';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 3000);
                } else {
                    alert('Erro ao atualizar perfil: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro ao processar requisição. Tente novamente.');
            });
        });
        
        // Botão de alterar senha
        document.getElementById('changePasswordBtn').addEventListener('click', function() {
            const novaSenha = prompt('Digite sua nova senha:');
            if (novaSenha && novaSenha.length >= 6) {
                const confirmarSenha = prompt('Confirme sua nova senha:');
                if (novaSenha === confirmarSenha) {
                    fetch('alterar_senha.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ senha: novaSenha })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Senha alterada com sucesso!');
                        } else {
                            alert('Erro ao alterar senha: ' + data.message);
                        }
                    });
                } else {
                    alert('As senhas não coincidem.');
                }
            } else {
                alert('A senha deve ter pelo menos 6 caracteres.');
            }
        });
    </script>
</body>
</html>