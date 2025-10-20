<?php
// Não há lógica PHP necessária, apenas para servir o arquivo
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navegação Espacial - Sistema Solar</title>
    <style>
        body {
            margin: 0;
            overflow: hidden;
            background: #000;
            font-family: Arial, sans-serif;
            color: white;
        }
        
        #gameCanvas {
            display: block;
            cursor: crosshair;
        }
        
        #info {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 10;
        }
        
        #controls {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 10;
        }
    </style>
</head>
<body>
    <canvas id="gameCanvas"></canvas>
    
    <div id="info">
        <div>Posição: <span id="position">X: 0, Y: 0</span></div>
        <div>Velocidade: <span id="speed">0</span></div>
        <div>Planeta mais próximo: <span id="nearestPlanet">Nenhum</span></div>
    </div>
    
    <div id="controls">
        <div><strong>Controles:</strong></div>
        <div>Setas: Rotacionar câmera</div>
        <div>W: Acelerar frente</div>
        <div>S: Acelerar ré</div>
        <div>A/D: Movimento lateral</div>
    </div>

    <script>
        // Configuração do canvas
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        
        // Ajustar tamanho do canvas
        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        // Constantes do jogo
        const WORLD_SIZE = 100000; // Tamanho do mundo em pixels
        const VIEWPORT_SIZE = 5000; // Tamanho da viewport visível
        const SHIP_SPEED = 2;
        const ROTATION_SPEED = 0.03;
        const FRICTION = 0.98;
        
        // Estado do jogo
        const ship = {
            x: WORLD_SIZE / 2,
            y: WORLD_SIZE / 2,
            rotation: 0,
            velocityX: 0,
            velocityY: 0,
            speed: 0
        };
        
        const camera = {
            x: ship.x,
            y: ship.y,
            rotation: ship.rotation
        };
        
        // Controles
        const keys = {};
        window.addEventListener('keydown', (e) => keys[e.key] = true);
        window.addEventListener('keyup', (e) => keys[e.key] = false);
        
        // Planetas do Sistema Solar
        const planets = [
            { name: 'Sol', x: WORLD_SIZE/2, y: WORLD_SIZE/2, radius: 150, image: '../images/sol.png' },
            { name: 'Mercúrio', x: WORLD_SIZE/2 - 800, y: WORLD_SIZE/2, radius: 15, image: '../images/mercurio.png' },
            { name: 'Vênus', x: WORLD_SIZE/2 - 1200, y: WORLD_SIZE/2, radius: 25, image: '../images/venus.png' },
            { name: 'Terra', x: WORLD_SIZE/2 - 1600, y: WORLD_SIZE/2, radius: 30, image: '../images/terra.png' },
            { name: 'Marte', x: WORLD_SIZE/2 - 2000, y: WORLD_SIZE/2, radius: 20, image: '../images/marte.png' },
            { name: 'Júpiter', x: WORLD_SIZE/2 + 3000, y: WORLD_SIZE/2, radius: 80, image: '../images/jupter.png' },
            { name: 'Saturno', x: WORLD_SIZE/2 + 5000, y: WORLD_SIZE/2, radius: 70, image: '../images/sartuno.png' },
            { name: 'Ceres', x: WORLD_SIZE/2 + 1000, y: WORLD_SIZE/2 - 1000, radius: 10, image: '../images/ceres.png' },
            { name: 'Eros', x: WORLD_SIZE/2 - 500, y: WORLD_SIZE/2 + 1500, radius: 8, image: '../images/eros.png' },
            { name: 'Ganimedes', x: WORLD_SIZE/2 + 3500, y: WORLD_SIZE/2 - 500, radius: 12, image: '../images/ganimedes.png' }
        ];
        
        // Carregar imagens dos planetas
        const planetImages = {};
        let loadedImages = 0;
        
        planets.forEach(planet => {
            const img = new Image();
            img.src = planet.image;
            img.onload = () => {
                loadedImages++;
                if (loadedImages === planets.length) {
                    gameLoop();
                }
            };
            planetImages[planet.name] = img;
        });
        
        // Carregar imagem de fundo
        const backgroundImage = new Image();
        backgroundImage.src = '../images/universo.jpg';
        
        // Atualizar estado do jogo
        function update() {
            // Rotação da câmera
            if (keys['ArrowLeft']) ship.rotation -= ROTATION_SPEED;
            if (keys['ArrowRight']) ship.rotation += ROTATION_SPEED;
            
            // Movimento da nave
            if (keys['w'] || keys['W']) {
                ship.velocityX += Math.cos(ship.rotation) * SHIP_SPEED;
                ship.velocityY += Math.sin(ship.rotation) * SHIP_SPEED;
            }
            if (keys['s'] || keys['S']) {
                ship.velocityX -= Math.cos(ship.rotation) * SHIP_SPEED;
                ship.velocityY -= Math.sin(ship.rotation) * SHIP_SPEED;
            }
            if (keys['a'] || keys['A']) {
                ship.velocityX += Math.cos(ship.rotation - Math.PI/2) * SHIP_SPEED;
                ship.velocityY += Math.sin(ship.rotation - Math.PI/2) * SHIP_SPEED;
            }
            if (keys['d'] || keys['D']) {
                ship.velocityX += Math.cos(ship.rotation + Math.PI/2) * SHIP_SPEED;
                ship.velocityY += Math.sin(ship.rotation + Math.PI/2) * SHIP_SPEED;
            }
            
            // Aplicar atrito
            ship.velocityX *= FRICTION;
            ship.velocityY *= FRICTION;
            
            // Calcular velocidade atual
            ship.speed = Math.sqrt(ship.velocityX * ship.velocityX + ship.velocityY * ship.velocityY);
            
            // Atualizar posição da nave
            ship.x += ship.velocityX;
            ship.y += ship.velocityY;
            
            // Limitar ao mundo
            ship.x = Math.max(0, Math.min(WORLD_SIZE, ship.x));
            ship.y = Math.max(0, Math.min(WORLD_SIZE, ship.y));
            
            // Atualizar câmera
            camera.x = ship.x;
            camera.y = ship.y;
            camera.rotation = ship.rotation;
            
            // Atualizar informações na tela
            document.getElementById('position').textContent = `X: ${Math.round(ship.x)}, Y: ${Math.round(ship.y)}`;
            document.getElementById('speed').textContent = ship.speed.toFixed(1);
            
            // Encontrar planeta mais próximo
            let nearestPlanet = null;
            let minDistance = Infinity;
            
            planets.forEach(planet => {
                const dx = planet.x - ship.x;
                const dy = planet.y - ship.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < minDistance) {
                    minDistance = distance;
                    nearestPlanet = planet.name;
                }
            });
            
            document.getElementById('nearestPlanet').textContent = nearestPlanet || 'Nenhum';
        }
        
        // Renderizar o jogo
        function render() {
            // Limpar canvas
            ctx.fillStyle = '#000';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Salvar estado do contexto
            ctx.save();
            
            // Mover para o centro da tela
            ctx.translate(canvas.width / 2, canvas.height / 2);
            
            // Aplicar rotação da câmera
            ctx.rotate(camera.rotation);
            
            // Desenhar fundo com paralaxe
            if (backgroundImage.complete) {
                const parallaxFactor = 0.1;
                const offsetX = (camera.x * parallaxFactor) % backgroundImage.width;
                const offsetY = (camera.y * parallaxFactor) % backgroundImage.height;
                
                ctx.drawImage(
                    backgroundImage,
                    offsetX - backgroundImage.width,
                    offsetY - backgroundImage.height,
                    backgroundImage.width * 2,
                    backgroundImage.height * 2
                );
            }
            
            // Desenhar planetas
            planets.forEach(planet => {
                // Calcular posição relativa à câmera
                const relX = planet.x - camera.x;
                const relY = planet.y - camera.y;
                
                // Calcular distância para perspectiva
                const distance = Math.sqrt(relX * relX + relY * relY);
                const perspectiveFactor = Math.max(0.2, Math.min(1, 1 - distance / VIEWPORT_SIZE));
                
                // Calcular tamanho aparente
                const apparentRadius = planet.radius * perspectiveFactor;
                
                // Desenhar planeta
                if (planetImages[planet.name] && planetImages[planet.name].complete) {
                    ctx.drawImage(
                        planetImages[planet.name],
                        relX - apparentRadius,
                        relY - apparentRadius,
                        apparentRadius * 2,
                        apparentRadius * 2
                    );
                } else {
                    // Fallback se imagem não carregou
                    ctx.fillStyle = '#888';
                    ctx.beginPath();
                    ctx.arc(relX, relY, apparentRadius, 0, Math.PI * 2);
                    ctx.fill();
                }
                
                // Desenhar nome do planeta
                if (perspectiveFactor > 0.3) {
                    ctx.fillStyle = 'white';
                    ctx.font = `${12 * perspectiveFactor}px Arial`;
                    ctx.textAlign = 'center';
                    ctx.fillText(planet.name, relX, relY + apparentRadius + 15 * perspectiveFactor);
                }
            });
            
            // Desenhar nave (triângulo)
            ctx.fillStyle = '#00f';
            ctx.beginPath();
            ctx.moveTo(0, -15);
            ctx.lineTo(-10, 10);
            ctx.lineTo(10, 10);
            ctx.closePath();
            ctx.fill();
            
            // Restaurar estado do contexto
            ctx.restore();
            
            // Desenhar mira no centro
            ctx.strokeStyle = 'rgba(255, 0, 0, 0.7)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(canvas.width/2 - 10, canvas.height/2);
            ctx.lineTo(canvas.width/2 + 10, canvas.height/2);
            ctx.moveTo(canvas.width/2, canvas.height/2 - 10);
            ctx.lineTo(canvas.width/2, canvas.height/2 + 10);
            ctx.stroke();
        }
        
        // Loop principal do jogo
        function gameLoop() {
            update();
            render();
            requestAnimationFrame(gameLoop);
        }
        
        // Iniciar o jogo quando todas as imagens estiverem carregadas
        if (loadedImages === planets.length) {
            gameLoop();
        }
    </script>
</body>
</html>