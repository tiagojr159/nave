<?php
session_start();
if(isset($_SESSION['usuario'])) {
    header("Location: mapa.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Espacial</title>
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

        .login-container {
            position: relative;
            width: 400px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 10;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-header p {
            color: #aaa;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #00d2ff;
            box-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 210, 255, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .form-options a {
            color: #00d2ff;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-options a:hover {
            text-decoration: underline;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #aaa;
        }

        .register-link a {
            color: #00d2ff;
            text-decoration: none;
            font-weight: bold;
        }

        .register-link a:hover {
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
    
    <div class="login-container">
        <div class="login-header">
            <h1>🚀</h1>
            <h1>Sistema Espacial</h1>
            <p>Acesse sua conta para explorar o universo</p>
        </div>
        
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <form action="verifica_login.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Sua senha" required>
            </div>
            
            <div class="form-options">
                <a href="#">Esqueceu a senha?</a>
                <a href="registro.php">Criar conta</a>
            </div>
            
            <button type="submit" class="btn-login">Entrar</button>
        </form>
        
        <div class="register-link">
            Não tem uma conta? <a href="registro.php">Registre-se</a>
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
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            
            // Resetar mensagens
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
            
            fetch('verifica_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.textContent = data.message;
                    successMessage.style.display = 'block';
                    
                    // Redirecionar após login bem-sucedido
                    setTimeout(() => {
                        window.location.href = 'mapa_astrados.php';
                    }, 1500);
                } else {
                    errorMessage.textContent = data.message;
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                errorMessage.textContent = 'Erro ao processar login. Tente novamente.';
                errorMessage.style.display = 'block';
            });
        });
    </script>
</body>
</html>