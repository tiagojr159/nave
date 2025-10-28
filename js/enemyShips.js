// enemyShips.js — naves inimigas fixas no universo, com interpolação e sprites
(function () {
  const WORLD_W = 100000;
  const WORLD_H = 100000;
  let enemies = [];
  let carregando = false;

  const enemyState = {}; // guarda posição e interpolação de cada nave

  // 🔹 Sprites das naves inimigas
  const SHIP_SPRITES = {
    idle: 'images/naves/1c.png', // motor off
    engine: 'images/naves/1d.png', // motor ligado
    left: 'images/naves/1b.png', // virando esquerda
    right: 'images/naves/1a.png', // virando direita
  };

  // Cache de imagens
  const spriteCache = {};
  for (const key in SHIP_SPRITES) {
    const img = new Image();
    img.src = SHIP_SPRITES[key];
    spriteCache[key] = img;
  }

  // 🔹 Atualiza naves inimigas (1x por segundo)
  async function carregarNavesOnline() {
    if (carregando) return;
    carregando = true;
    try {
      const idAtual = window.ship?.id || 0;
      const res = await fetch(`db/get_naves_online.php?id=${idAtual}`, { cache: "no-store" });
      const data = await res.json();

      if (Array.isArray(data)) {
        enemies = data;
        for (const e of enemies) {
          if (!enemyState[e.id]) {
            enemyState[e.id] = {
              x: e.x, y: e.y,
              targetX: e.x, targetY: e.y,
              heading: 0,
              speed: 0.002
            };
          } else {
            const s = enemyState[e.id];
            s.targetX = e.x;
            s.targetY = e.y;
          }
        }
      }
    } catch (e) {
      console.error("❌ Erro ao carregar naves online:", e);
    } finally {
      carregando = false;
    }
  }

  setInterval(carregarNavesOnline, 1000);
  document.addEventListener("DOMContentLoaded", carregarNavesOnline);

  // 🔹 Easing progressivo
  function easeInOut(t) {
    return t * t * (3 - 2 * t);
  }

  // 🔹 Atualiza interpolação de movimento
  function updateEnemies(dt) {
    const BASE_SPEED = 0.002;
    const MAX_SPEED = 0.03;
    const SPEED_GROWTH = 0.0005;

    for (const e of enemies) {
      const s = enemyState[e.id];
      if (!s) continue;

      // Aceleração gradual
      s.speed = Math.min(MAX_SPEED, s.speed + SPEED_GROWTH * dt);

      // Interpolação
      const dx = s.targetX - s.x;
      const dy = s.targetY - s.y;
      const dist = Math.hypot(dx, dy);

      if (dist > 0.1) {
        const t = easeInOut(s.speed);
        s.x += dx * t;
        s.y += dy * t;
        s.heading = Math.atan2(dy, dx);
      }
    }
  }

  // 🔹 Desenha as naves inimigas com base na posição interpolada
  function drawEnemyShips(ctx, width, height) {
    if (!window.ship || !enemies.length) return;

    const FOV = 60;
    const MAX_DRAW_DIST = 40000;

    for (const e of enemies) {
      const s = enemyState[e.id];
      if (!s) continue;

      const dx = (s.x ?? e.x) - ship.x;
      const dy = (s.y ?? e.y) - ship.y;
      const dist = Math.hypot(dx, dy);
      if (!isFinite(dist) || dist > MAX_DRAW_DIST) continue;

      const angToEnemy = Math.atan2(dy, dx);
      let rel = angToEnemy - (ship.heading * Math.PI / 180);
      rel = Math.atan2(Math.sin(rel), Math.cos(rel));

      const screenX = (width / 2) + (rel * (width / Math.PI));
      const screenY = height * 0.6 + ship.pitch * 3;
      if (screenX < -100 || screenX > width + 100) continue;

      // Escala baseada na distância
      const scale = Math.min(Math.max((FOV / Math.max(60, dist)), 0.03), 3.0);
      let size = 32 * scale;

      const sprite = spriteCache.engine || spriteCache.idle;

      ctx.save();
      ctx.translate(screenX, screenY);
      ctx.rotate(s.heading);
      ctx.shadowColor = "#ff5555";
      ctx.shadowBlur = Math.max(5, 20 - dist / 2000);
      ctx.drawImage(sprite, -size / 2, -size / 2, size, size);
      ctx.restore();

      if (dist < 8000) {
        ctx.fillStyle = "#fff";
        ctx.font = "12px system-ui, Arial";
        ctx.textAlign = "center";
        ctx.fillText(`${e.nome?.toUpperCase() || "INIMIGO"}`, screenX, screenY - size - 5);
      }
    }
  }

  window.enemyShips = {
    drawEnemyShips,
    updateEnemies,
    get enemies() { return enemies; }
  };
})();
