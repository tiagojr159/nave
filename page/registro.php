<?php
session_start();
if(isset($_SESSION['usuario'])) {
require_once '../db/conexao.php';
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema Espacial</title>
    <style>
        /* Reutilizar estilos do login.php */
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
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
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

        .register-container {
            position: relative;
            width: 450px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 10;
            max-height: 90vh;
            overflow-y: auto;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .register-header p {
            color: #aaa;
            font-size: 1rem;
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

        .btn-register {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 210, 255, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #aaa;
        }

        .login-link a {
            color: #00d2ff;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: rgba(244, 67, 54, 0.2);
            border-left: 4px solid #f44336;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
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
            left: 10%;
            animation-duration: 25s;
        }

        .planet2 {
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, #FF6347, #CD5C5C);
            bottom: 15%;
            right: 15%;
            animation-duration: 30s;
        }

        .planet3 {
            width: 40px;
            height: 40px;
            background: radial-gradient(circle, #FFD700, #FFA500);
            top: 20%;
            right: 20%;
            animation-duration: 20s;
        }
    </style>
</head>
<body>
    <!-- Animação de fundo com estrelas -->
    <div class="stars" id="stars"></div>
    
    <!-- Objetos espaciais flutuantes -->
    <div class="space-object planet1"></div>
    <div class="space-object planet2"></div>
    <div class="space-object planet3"></div>
    
    <div class="register-container">
        <div class="register-header">
            <h1>🌌</h1>
            <h1>Criar Conta</h1>
            <p>Junte-se à exploração espacial</p>
        </div>
        
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <form action="processa_registro.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            </div>
            
            <div class="form-group">
                <label for="apelido">Apelido</label>
                <input type="text" id="apelido" name="apelido" placeholder="Seu apelido ou username" required>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição (opcional)</label>
                <textarea id="descricao" name="descricao" placeholder="Conte um pouco sobre você..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Crie uma senha forte" required>
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita sua senha" required>
            </div>
            
            <button type="submit" class="btn-register">Criar Conta</button>
        </form>
        
        <div class="login-link">
            Já tem uma conta? <a href="login.php">Faça login</a>
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
        
        // Processar formulário com AJAX
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            
            // Resetar mensagens
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
            
            fetch('processa_registro.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.textContent = data.message;
                    successMessage.style.display = 'block';
                    
                    // Redirecionar após registro bem-sucedido
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    errorMessage.textContent = data.message;
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                errorMessage.textContent = 'Erro ao processar registro. Tente novamente.';
                errorMessage.style.display = 'block';
            });
        });
    </script>
</body>
</html>