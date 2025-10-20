<?php
// index.php - Jogo do Sistema Solar Simplificado qwen
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sistema Solar - Nave Espacial</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      overflow: hidden;
      background: black;
      font-family: Arial, sans-serif;
    }

    #universe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('images/universo.jpg');
      background-size: cover;
      background-position: center;
    }

    .planet {
      position: absolute;
      transform: translate(-50%, -50%);
      transition: transform 0.1s ease;
      image-rendering: -webkit-optimize-contrast;
      image-rendering: crisp-edges;
    }

    #ship {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 20px;
      height: 20px;
      background: rgba(0, 255, 255, 0.3);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
      z-index: 1000;
    }

    #instructions {
      position: absolute;
      bottom: 10px;
      left: 10px;
      color: white;
      background: rgba(0,0,0,0.6);
      padding: 8px;
      font-size: 14px;
      border-radius: 4px;
      z-index: 2000;
    }
  </style>
</head>
<body>
  <div id="universe">
    <div id="ship"></div>
  </div>
  <div id="instructions">
    Use <b>WASD</b> para mover a nave • Use <b>Setas</b> para olhar ao redor
  </div>

  <script>
    // Configurações iniciais
    const worldSize = 100000; // 100.000 pixels de área (simulada)
    const player = {
      x: 0,
      y: 0,
      angle: 0 // em radianos
    };

    // Planetas: nome, x, y, imagem, escala base
    const planets = [
      { name: 'Sol', x: 0, y: 0, img: '../images/sol.png', baseScale: 0.8 },
      { name: 'Mercúrio', x: 15000, y: 0, img: 'venus.png', baseScale: 0.15 }, // reutilizando Vênus por falta de Mercúrio
      { name: 'Vênus', x: 25000, y: 5000, img: 'venus.png', baseScale: 0.25 },
      { name: 'Terra', x: 35000, y: -3000, img: 'terra.png', baseScale: 0.28 },
      { name: 'Marte', x: 45000, y: 8000, img: 'marte.png', baseScale: 0.2 },
      { name: 'Júpiter', x: 65000, y: -10000, img: 'jupter.png', baseScale: 0.6 },
      { name: 'Saturno', x: 80000, y: 15000, img: 'sartuno.png', baseScale: 0.5 },
      { name: 'Ganimedes', x: 68000, y: -8000, img: 'ganimedes.png', baseScale: 0.1 },
      { name: 'Ceres', x: 50000, y: 20000, img: 'ceres.png', baseScale: 0.08 },
      { name: 'Eros', x: 55000, y: -18000, img: 'eros.png', baseScale: 0.06 },
      { name: 'Asteróide', x: 70000, y: 25000, img: 'asteroide.png', baseScale: 0.07 }
    ];

    const universe = document.getElementById('universe');
    const planetElements = [];

    // Cria os elementos dos planetas
    planets.forEach(planet => {
      const el = document.createElement('img');
      el.src = '../images/' + planet.img;
      el.className = 'planet';
      el.style.width = (planet.baseScale * 100) + 'px';
      el.style.height = 'auto';
      el.dataset.x = planet.x;
      el.dataset.y = planet.y;
      el.dataset.baseScale = planet.baseScale;
      universe.appendChild(el);
      planetElements.push(el);
    });

    // Controles
    const keys = {};
    window.addEventListener('keydown', e => keys[e.key.toLowerCase()] = true);
    window.addEventListener('keyup', e => keys[e.key.toLowerCase()] = false);

    // Função para atualizar a posição e escala dos planetas
    function updateView() {
      planetElements.forEach((el, i) => {
        const px = parseFloat(el.dataset.x);
        const py = parseFloat(el.dataset.y);
        const dx = px - player.x;
        const dy = py - player.y;
        const distance = Math.sqrt(dx * dx + dy * dy);

        // Calcula posição na tela com base na direção do jogador (ângulo)
        const rotatedX = dx * Math.cos(-player.angle) - dy * Math.sin(-player.angle);
        const rotatedY = dx * Math.sin(-player.angle) + dy * Math.cos(-player.angle);

        // Só mostra se estiver "na frente"
        if (rotatedX > 0) {
          const scale = Math.min(1, 5000 / (distance + 1)); // Quanto mais perto, maior
          const screenX = (rotatedY / rotatedX) * window.innerWidth / 2 + window.innerWidth / 2;
          const screenY = window.innerHeight / 2 - (rotatedY / rotatedX) * window.innerHeight / 4;

          el.style.left = screenX + 'px';
          el.style.top = screenY + 'px';
          el.style.transform = `translate(-50%, -50%) scale(${scale})`;
          el.style.display = 'block';
        } else {
          el.style.display = 'none';
        }
      });
    }

    // Loop principal
    function gameLoop() {
      const speed = 800; // pixels por segundo
      const rotSpeed = 0.03; // radianos por frame
      const dt = 1/60; // delta time fixo

      // Movimento (WASD)
      if (keys['w']) {
        player.x += Math.cos(player.angle) * speed * dt;
        player.y += Math.sin(player.angle) * speed * dt;
      }
      if (keys['s']) {
        player.x -= Math.cos(player.angle) * speed * dt;
        player.y -= Math.sin(player.angle) * speed * dt;
      }
      if (keys['a']) {
        player.x += Math.cos(player.angle - Math.PI/2) * speed * dt;
        player.y += Math.sin(player.angle - Math.PI/2) * speed * dt;
      }
      if (keys['d']) {
        player.x += Math.cos(player.angle + Math.PI/2) * speed * dt;
        player.y += Math.sin(player.angle + Math.PI/2) * speed * dt;
      }

      // Rotação (setas)
      if (keys['arrowleft']) player.angle -= rotSpeed;
      if (keys['arrowright']) player.angle += rotSpeed;
      if (keys['arrowup']) player.angle -= rotSpeed / 2;
      if (keys['arrowdown']) player.angle += rotSpeed / 2;

      // Normaliza ângulo
      player.angle = ((player.angle % (Math.PI * 2)) + Math.PI * 2) % (Math.PI * 2);

      updateView();
      requestAnimationFrame(gameLoop);
    }

    // Inicia o jogo
    gameLoop();
  </script>
</body>
</html>