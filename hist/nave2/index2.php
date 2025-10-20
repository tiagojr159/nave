<?php
// index.php — Space Run: Sistema Solar 2D em “primeira pessoa” (um único nível)
// Requisitos: apenas PHP (para entregar o arquivo), HTML, CSS e JavaScript puro.
// Controles: ← → giram a nave; ↑ ↓ ajustam a câmera; A acelera; B freia/inverte; R recentra; M mostra/oculta minimapa.
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Space Run — Sistema Solar (1 arquivo)</title>
<style>
  :root{
    --bg:#000; --hud:#8be9fd; --accent:#5865f2; --danger:#ff5555; --ok:#50fa7b;
  }
  *{box-sizing:border-box}
  html,body{height:100%;margin:0;background:#000;font-family:system-ui,Segoe UI,Arial,sans-serif;color:#eaeaea}
  .wrap{position:relative;width:100%;height:100%;overflow:hidden;background:#000}
  canvas#view{position:absolute;inset:0;width:100%;height:100%;display:block;background:#000}
  /* HUD */
  .hud{
    position:absolute;left:12px;top:12px;z-index:20;display:flex;gap:.5rem;flex-wrap:wrap;
    font-size:12px;color:var(--hud);text-shadow:0 0 6px #0ff4;
    background:#0007;border:1px solid #0ff3;border-radius:10px;padding:.6rem .8rem;backdrop-filter:blur(4px)
  }
  .hud b{color:#fff}
  .legend{position:absolute;right:12px;top:12px;z-index:20;background:#0007;border:1px solid #0ff3;border-radius:10px;padding:.6rem .8rem;font-size:12px}
  .legend kbd{background:#111;border:1px solid #333;border-bottom-width:2px;border-radius:6px;padding:.1rem .35rem;margin:0 .1rem;color:#ddd}
  /* Minimapa */
  .minimap{
    position:absolute;right:12px;bottom:12px;z-index:20;width:220px;height:140px;
    background:#05070d;border:1px solid #2c3e50;border-radius:12px;overflow:hidden;box-shadow:0 0 24px #000 inset
  }
  .minimap .title{font-size:11px;text-align:center;color:#9aa; padding:4px 0;border-bottom:1px solid #2c3e50}
  .minimap canvas{display:block;width:100%;height:calc(100% - 20px)}
  .toast{
    position:absolute;left:50%;transform:translateX(-50%);bottom:24px;z-index:25;
    background:#0b1320;border:1px solid #334; color:#cde; padding:.6rem .9rem;border-radius:10px;font-size:12px;opacity:.85
  }
</style>
</head>
<body>
<div class="wrap">
  <canvas id="view"></canvas>

  <div class="hud" id="hud">
    <div>vel: <b id="vel">0</b></div>
    <div>rumo: <b id="hdg">0°</b></div>
    <div>pos: <b id="pos">0,0</b></div>
    <div>alvo: <b id="tgt">—</b></div>
  </div>

  <div class="legend">
    <div><b>Controles</b></div>
    <div><kbd>←</kbd>/<kbd>→</kbd> girar • <kbd>↑</kbd>/<kbd>↓</kbd> olhar</div>
    <div><kbd>A</kbd> acelerar • <kbd>B</kbd> frear</div>
    <div><kbd>M</kbd> minimapa • <kbd>R</kbd> reset</div>
  </div>

  <div class="minimap" id="minimap">
    <div class="title">Mapa (100.000 × 100.000 px)</div>
    <canvas id="map"></canvas>
  </div>

  <div class="toast" id="toast" style="display:none"></div>
</div>

<script>
/* ========= Config do Mundo ========= */
const WORLD_W = 100000;      // largura virtual do “setor”
const WORLD_H = 100000;      // altura virtual — agora você pode voar o quadradão inteiro
const FOV = 900;             // campo de visão para “escala por distância”
const MAX_DRAW_DIST = 20000; // recorte de renderização (desempenho)
const STAR_COUNT = 400;      // estrelas na esfera celeste
const IMG_PATH = "../images/"; // base das imagens fornecidas

// Catálogo (raios/nomes/imagens fixos; posições serão aleatórias)
const bodies = [
  {name:"Sol",        img:"sol.png",        x:0, y:0, r:1200},
  {name:"Mercúrio",   img:"eros.png",       x:0, y:0, r:120},
  {name:"Vênus",      img:"venus.png",      x:0, y:0, r:350},
  {name:"Terra",      img:"terra.png",      x:0, y:0, r:360},
  {name:"Marte",      img:"marte.png",      x:0, y:0, r:270},
  {name:"Ceres",      img:"ceres.png",      x:0, y:0, r:120},
  {name:"Júpiter",    img:"jupter.png",     x:0, y:0, r:900},
  {name:"Ganimedes",  img:"ganimedes.png",  x:0, y:0, r:260},
  {name:"Saturno",    img:"sartuno.png",    x:0, y:0, r:800},
  {name:"Asteróide",  img:"asteroide.png",  x:0, y:0, r:80},
  {name:"Netuno",     img:"netuno.png",     x:0, y:0, r:520},
];

// Carrega imagens
const cache = {};
function loadImage(src){
  return new Promise(res=>{
    const im = new Image();
    im.src = IMG_PATH + src;
    im.onload = ()=>res(im);
    im.onerror = ()=>{ console.warn("Falha ao carregar", src); res(null); };
  });
}

// Nave / câmera
const ship = {
  x: 500, y: 500,            // começa dentro do setor
  heading: 0,                // graus (0 = +x)
  pitch: 0,                  // apenas efeito visual
  vel: 0,                    // px/s
  acc: 0,                    // aceleração atual
  maxSpeed: 1800,            // limite
  turnRate: 120,             // deg/s
};

// UI/Canvas
const view = document.getElementById('view');
const ctx = view.getContext('2d');
const mapCanvas = document.getElementById('map');
const mapCtx = mapCanvas.getContext('2d');
const hudVel = document.getElementById('vel');
const hudHdg = document.getElementById('hdg');
const hudPos = document.getElementById('pos');
const hudTgt = document.getElementById('tgt');
const toast = document.getElementById('toast');
const minimapEl = document.getElementById('minimap');

let W = 0, H = 0, stars = [], showMap = true, lastT = 0;

// Estrelas para fundo
function makeStars(){
  stars = [];
  for(let i=0;i<STAR_COUNT;i++){
    stars.push({ 
      x: Math.random()*WORLD_W, 
      y: (Math.random()-0.5)*WORLD_H*2, 
      r: Math.random()*1.6+0.4, 
      a: Math.random()*0.6+0.2 
    });
  }
}

// ======= Distribuição aleatória dos corpos =======
// - Todos dentro do retângulo 0..WORLD_W × 0..WORLD_H
// - Distância mínima entre corpos (evita aglomero)
// - Distância mínima da posição inicial da nave (evita “spawn kill” solar 😅)
function randomizeBodies(){
  const placed = [];
  const minGap = 1400;        // separação mínima base
  const minFromShip = 5000;   // não jogue um planeta na cara da nave ao iniciar

  function ok(x,y,r){
    // dentro dos limites com margem do raio
    if(x < r || y < r || x > WORLD_W - r || y > WORLD_H - r) return false;
    // longe da nave
    const ds = Math.hypot(x - ship.x, y - ship.y);
    if(ds < Math.max(minFromShip, r*3)) return false;
    // longe dos já colocados
    for(const p of placed){
      const d = Math.hypot(x - p.x, y - p.y);
      const need = (r + p.r) + minGap * 0.5;
      if(d < need) return false;
    }
    return true;
  }

  for(const b of bodies){
    let tries = 0, found = false;
    while(tries++ < 500 && !found){
      const x = Math.random() * WORLD_W;
      const y = Math.random() * WORLD_H;
      if(ok(x,y,b.r)){
        b.x = x; b.y = y;
        placed.push({x,y,r:b.r});
        found = true;
      }
    }
    // fallback: se não conseguir, coloca em algum ponto amplo (centro deslocado)
    if(!found){
      const x = WORLD_W*0.5 + (Math.random()-0.5)*WORLD_W*0.8;
      const y = WORLD_H*0.5 + (Math.random()-0.5)*WORLD_H*0.8;
      b.x = Math.min(WORLD_W-b.r, Math.max(b.r, x));
      b.y = Math.min(WORLD_H-b.r, Math.max(b.r, y));
      placed.push({x:b.x, y:b.y, r:b.r});
    }
  }
}

// Ajusta tamanhos
function resize(){
  view.width = W = view.clientWidth;
  view.height = H = view.clientHeight;
  mapCanvas.width = minimapEl.clientWidth;
  mapCanvas.height = minimapEl.clientHeight - 20;
}
window.addEventListener('resize', resize);

// Controles
const keys = {};
window.addEventListener('keydown', e=>{
  keys[e.key.toLowerCase()] = true;
  if(["ArrowLeft","ArrowRight","ArrowUp","ArrowDown"].includes(e.key)) e.preventDefault();
  if(e.key.toLowerCase()==='m'){ showMap = !showMap; minimapEl.style.display = showMap?'block':'none'; }
  if(e.key.toLowerCase()==='r'){ ship.x=500; ship.y=500; ship.heading=0; ship.vel=0; flash("POSIÇÃO RESETADA"); }
});
window.addEventListener('keyup', e=>{ keys[e.key.toLowerCase()] = false; });

function flash(msg){
  toast.textContent = msg;
  toast.style.display='block';
  clearTimeout(flash._t); flash._t = setTimeout(()=>toast.style.display='none', 1500);
}

/* ======= Simulação + Render ======= */
function clamp(v,a,b){ return Math.max(a, Math.min(b, v)); }
function deg2rad(d){ return d*Math.PI/180; }

function update(dt){
  // Rotação por setas
  if(keys['arrowleft'])  ship.heading -= ship.turnRate*dt;
  if(keys['arrowright']) ship.heading += ship.turnRate*dt;
  // Inclinação “visual”
  if(keys['arrowup'])    ship.pitch = clamp(ship.pitch - 40*dt, -20, 20);
  if(keys['arrowdown'])  ship.pitch = clamp(ship.pitch + 40*dt, -20, 20);
  ship.pitch *= (1-0.08); // amortecimento

  // Aceleração: A acelera; B freia/inverte
  if(keys['a']) ship.acc = 1400;
  else if(keys['b']) ship.acc = -1000;
  else ship.acc = 0;

  // Integra velocidade
  ship.vel += ship.acc*dt;
  ship.vel = clamp(ship.vel, -ship.maxSpeed*0.5, ship.maxSpeed);
  // Atrito suave
  ship.vel *= (1 - 0.15*dt);

  // Movimento no plano seguindo heading
  const rad = deg2rad(ship.heading);
  ship.x += Math.cos(rad)*ship.vel*dt;
  ship.y += Math.sin(rad)*ship.vel*dt;

  // Limites do mundo — agora é o setor inteiro
  ship.x = clamp(ship.x, 0, WORLD_W);
  ship.y = clamp(ship.y, 0, WORLD_H);
}

function draw(){
  // Fundo
  ctx.fillStyle = '#000';
  ctx.fillRect(0,0,W,H);

  // Estrelas (paralaxe simples por heading)
  const starOffsetX = (ship.heading%360)/360*2000;
  ctx.save();
  ctx.translate(-(starOffsetX%2000), H/2 + ship.pitch*2);
  for(const s of stars){
    const sx = (s.x % 2000);
    const sy = (s.y % 4000);
    ctx.globalAlpha = s.a;
    ctx.fillStyle = '#9ecbff';
    ctx.beginPath();
    ctx.arc(sx, sy, s.r, 0, Math.PI*2);
    ctx.fill();
  }
  ctx.restore();
  ctx.globalAlpha = 1;

  // Desenho dos corpos com escala por distância
  let nearestName = "—", nearestDist = Infinity;

  for(const b of bodies){
    const dx = b.x - ship.x;
    const dy = b.y - ship.y;
    const dist = Math.hypot(dx, dy);

    if(dist < nearestDist){ nearestDist = dist; nearestName = b.name; }

    if(dist > MAX_DRAW_DIST) continue;  // recorte

    // Ângulo do corpo relativo ao heading (para projetar no eixo horizontal da tela)
    const angToBody = Math.atan2(dy, dx);              // rad
    let rel = angToBody - deg2rad(ship.heading);
    // normaliza [-PI, PI]
    rel = Math.atan2(Math.sin(rel), Math.cos(rel));

    // Converte ângulo relativo em posição X na tela (centro = heading)
    const screenX = (W/2) + (rel * (W/Math.PI)); // -π→esq, +π→dir
    // “Altura” visual (pitch só desloca verticalmente para sensação)
    const screenY = H*0.6 + ship.pitch*3;

    // Tamanho aparente por distância
    const scale = clamp((FOV / Math.max(60, dist)), 0.02, 3.5);
    const rad = b.r * scale;

    // Desenho (imagem se disponível, senão um círculo fallback)
    const im = cache[b.img];
    if(im){
      ctx.save();
      ctx.translate(screenX, screenY);
      const d = rad*2;
      ctx.drawImage(im, -d/2, -d/2, d, d);
      ctx.restore();
    }else{
      ctx.save();
      ctx.translate(screenX, screenY);
      ctx.fillStyle = '#3f46d9';
      ctx.beginPath(); ctx.arc(0,0, rad, 0, Math.PI*2); ctx.fill();
      ctx.restore();
    }

    // Nome discreto quando grande o suficiente
    if(rad > 24){
      ctx.fillStyle = '#8aa2ff';
      ctx.font = '12px system-ui, Arial';
      ctx.textAlign='center';
      ctx.fillText(b.name, screenX, screenY - rad - 8);
    }
  }

  // HUD
  hudVel.textContent = ship.vel.toFixed(0);
  hudHdg.textContent = ( (ship.heading%360+360)%360 ).toFixed(0) + '°';
  hudPos.textContent = `${ship.x.toFixed(0)},${ship.y.toFixed(0)}`;
  hudTgt.textContent = `${nearestName} (${nearestDist.toFixed(0)}px)`;

  // “Retícula” simples
  ctx.strokeStyle = '#5865f299';
  ctx.beginPath();
  ctx.arc(W/2, H*0.6, 20, 0, Math.PI*2);
  ctx.moveTo(W/2-28, H*0.6); ctx.lineTo(W/2+28, H*0.6);
  ctx.stroke();
}

function drawMiniMap(){
  if(!showMap) return;
  const mW = mapCanvas.width, mH = mapCanvas.height;
  mapCtx.clearRect(0,0,mW,mH);
  // moldura
  mapCtx.fillStyle = '#0b1220';
  mapCtx.fillRect(0,0,mW,mH);
  // escala mundo → mapa
  const sx = mW / WORLD_W;
  const sy = mH / WORLD_H;
  // planetas
  for(const b of bodies){
    const x = b.x * sx;
    const y = b.y * sy;
    mapCtx.fillStyle = '#3e50ff';
    mapCtx.beginPath(); mapCtx.arc(x, y, Math.max(1.5, b.r*sx*0.4), 0, Math.PI*2); mapCtx.fill();
  }
  // nave
  const nx = ship.x*sx, ny = ship.y*sy;
  mapCtx.fillStyle = '#ff5a5a';
  mapCtx.beginPath(); mapCtx.arc(nx, ny, 3.5, 0, Math.PI*2); mapCtx.fill();
  // rumo
  mapCtx.strokeStyle = '#ff8a8a';
  mapCtx.beginPath();
  mapCtx.moveTo(nx, ny);
  const r = deg2rad(ship.heading);
  mapCtx.lineTo(nx + Math.cos(r)*18, ny + Math.sin(r)*18);
  mapCtx.stroke();
}

async function boot(){
  resize();
  makeStars();
  randomizeBodies(); // <<< distribuição aleatória no setor 100k x 100k

  // carrega imagens
  await Promise.all(bodies.map(async b=>{
    const im = await loadImage(b.img);
    if(im) cache[b.img]=im;
  }));
  flash("Bem-vindo! Acelere com A, freie com B. Gire com ← →. Setor 100k x 100k liberado. 🚀");
  lastT = performance.now();
  requestAnimationFrame(loop);
}

function loop(t){
  const dt = Math.min(0.05, (t - lastT)/1000);
  lastT = t;
  update(dt);
  draw();
  drawMiniMap();
  requestAnimationFrame(loop);
}

boot();
</script>
</body>
</html>
