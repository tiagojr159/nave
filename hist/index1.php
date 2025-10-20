<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🚀 Jogo de Nave Espacial</title>
<style>
  body {
    margin: 0;
    overflow: hidden;
    background: black;
  }
  canvas {
    display: block;
    width: 100vw;
    height: 100vh;
  }
  .hud {
    position: fixed;
    top: 10px;
    left: 10px;
    color: #00ffcc;
    font-family: monospace;
    text-shadow: 0 0 6px #00ffcc;
  }
  @media (max-width: 768px) {
    .hud { font-size: 14px; }
  }
</style>
</head>
<body>
<canvas id="game"></canvas>

<!-- HUD -->
<div class="hud" id="hud">
  <div id="score">Pontuação: 0</div>
  <div id="energy">Energia: ██████</div>
  <div>Controles: ← → ↑ ↓ | Espaço = Tiro</div>
</div>

<!-- Som do tiro -->
<audio id="laser" preload="auto"
  src="https://cdn.pixabay.com/download/audio/2021/08/04/audio_553a2bfbcf.mp3?filename=laser-gun-shot-01-51352.mp3">
</audio>

<script>
// =====================================================
// CONFIGURAÇÃO INICIAL
// =====================================================
const canvas = document.getElementById('game');
const ctx = canvas.getContext('2d');

function resize() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
}
window.addEventListener('resize', resize);
resize();

// =====================================================
// FUNÇÃO DE CARREGAMENTO SEGURO DE IMAGENS
// =====================================================
function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = "anonymous";
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = src;
  });
}

// =====================================================
// LINKS DE IMAGENS LIVRES (CORS OK)
// =====================================================
const IMAGES = {
  bg: "https://images.unsplash.com/photo-1476610182048-b716b8518aae?auto=format&fit=crop&w=1600&q=80",
  ship: "https://opengameart.org/sites/default/files/spaceship_3_0.png",
  asteroid: "https://upload.wikimedia.org/wikipedia/commons/4/4e/Asteroid_Bennu_-_OSIRIS-REx.png"
};

// =====================================================
// VARIÁVEIS DO JOGO
// =====================================================
let assets = {};
const keys = {};
const stars = [];
const shots = [];
const asteroids = [];
let score = 0;
let energy = 6;

const player = {
  x: window.innerWidth / 2,
  y: window.innerHeight * 0.8,
  w: 80,
  h: 80,
  speed: 7
};

// =====================================================
// EVENTOS DE TECLADO
// =====================================================
document.addEventListener('keydown', e => keys[e.key] = true);
document.addEventListener('keyup', e => keys[e.key] = false);

function moverNave() {
  if (keys['ArrowLeft']) player.x -= player.speed;
  if (keys['ArrowRight']) player.x += player.speed;
  if (keys['ArrowUp']) player.y -= player.speed;
  if (keys['ArrowDown']) player.y += player.speed;
  player.x = Math.max(player.w / 2, Math.min(canvas.width - player.w / 2, player.x));
  player.y = Math.max(player.h / 2, Math.min(canvas.height - player.h / 2, player.y));
}

// =====================================================
// TIROS
// =====================================================
function atirar() {
  shots.push({ x: player.x, y: player.y - 40 });
  const sfx = document.getElementById("laser");
  sfx.currentTime = 0;
  sfx.play().catch(()=>{});
}
document.addEventListener('keydown', e => {
  if (e.key === ' ') atirar();
});

// =====================================================
// ESTRELAS DE FUNDO
// =====================================================
function criarEstrelas(qtd) {
  for (let i = 0; i < qtd; i++) {
    stars.push({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      z: Math.random() * 2 + 0.5
    });
  }
}

// =====================================================
// ASTEROIDES
// =====================================================
function criarAsteroide() {
  asteroids.push({
    x: Math.random() * canvas.width,
    y: -60,
    r: 40 + Math.random() * 30,
    vy: 2 + Math.random() * 2
  });
}

// =====================================================
// ATUALIZAÇÃO (UPDATE)
// =====================================================
function update() {
  // Movimento das estrelas
  for (const s of stars) {
    s.y += s.z;
    if (s.y > canvas.height) {
      s.y = 0;
      s.x = Math.random() * canvas.width;
    }
  }

  // Movimento dos tiros
  for (let i = shots.length - 1; i >= 0; i--) {
    const t = shots[i];
    t.y -= 10;
    if (t.y < 0) shots.splice(i, 1);
  }

  // Movimento dos asteroides
  for (let i = asteroids.length - 1; i >= 0; i--) {
    const a = asteroids[i];
    a.y += a.vy;
    if (a.y > canvas.height + 80) asteroids.splice(i, 1);
  }

  // Colisões tiro ↔ asteroide
  for (let i = asteroids.length - 1; i >= 0; i--) {
    for (let j = shots.length - 1; j >= 0; j--) {
      const a = asteroids[i];
      const t = shots[j];
      const dx = a.x - t.x, dy = a.y - t.y;
      if (dx * dx + dy * dy < a.r * a.r * 0.6) {
        asteroids.splice(i, 1);
        shots.splice(j, 1);
        score += 10;
        break;
      }
    }
  }

  // Movimento da nave
  moverNave();

  // Criar novos asteroides aleatórios
  if (Math.random() < 0.02) criarAsteroide();
}

// =====================================================
// DESENHAR (DRAW)
// =====================================================
function draw() {
  // Fundo
  if (assets.bg) ctx.drawImage(assets.bg, 0, 0, canvas.width, canvas.height);
  else {
    ctx.fillStyle = 'black';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
  }

  // Estrelas
  ctx.fillStyle = 'white';
  for (const s of stars) ctx.fillRect(s.x, s.y, s.z, s.z);

  // Asteroides
  for (const a of asteroids) {
    if (assets.asteroid)
      ctx.drawImage(assets.asteroid, a.x - a.r, a.y - a.r, a.r * 2, a.r * 2);
    else {
      ctx.fillStyle = 'gray';
      ctx.beginPath();
      ctx.arc(a.x, a.y, a.r, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  // Tiros
  ctx.fillStyle = 'yellow';
  for (const t of shots) ctx.fillRect(t.x - 2, t.y - 10, 4, 10);

  // Nave
  if (assets.ship)
    ctx.drawImage(assets.ship, player.x - player.w / 2, player.y - player.h / 2, player.w, player.h);
  else {
    ctx.fillStyle = 'cyan';
    ctx.fillRect(player.x - 20, player.y - 15, 40, 30);
  }

  // HUD
  document.getElementById('score').textContent = "Pontuação: " + score;
  document.getElementById('energy').textContent = "Energia: " + "█".repeat(energy);
}

// =====================================================
// LOOP PRINCIPAL
// =====================================================
function loop() {
  update();
  draw();
  requestAnimationFrame(loop);
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================
Promise.all([
  loadImage(IMAGES.bg),
  loadImage(IMAGES.ship),
  loadImage(IMAGES.asteroid)
]).then(([bg, ship, asteroid]) => {
  assets = { bg, ship, asteroid };
  criarEstrelas(150);
  loop();
}).catch(err => {
  console.error("Erro ao carregar imagens:", err);
  criarEstrelas(150);
  loop();
});
</script>
</body>
</html>
