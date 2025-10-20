<?php
// index.php — Space Run (mundo 100k x 100k com planetas aleatórios num só nível)
// Controles: ← → girar | ↑ ↓ olhar | A acelerar | B frear | R reset | M minimapa
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Space Run — Quadrante 100k²</title>
<style>
  :root{ --bg:#000; --hud:#8be9fd; --accent:#5865f2; --danger:#ff5555; }
  *{box-sizing:border-box}
  html,body{height:100%;margin:0;background:#000;color:#eaeaea;font-family:system-ui,Segoe UI,Arial}
  .wrap{position:relative;width:100%;height:100%;overflow:hidden;background:#000}
  canvas#view{position:absolute;inset:0;width:100%;height:100%;display:block;background:#000}
  .hud{position:absolute;left:12px;top:12px;z-index:20;display:flex;gap:.6rem;flex-wrap:wrap;
       font-size:12px;color:#8be9fd;text-shadow:0 0 6px #0ff4;background:#0007;border:1px solid #0ff3;
       border-radius:10px;padding:.6rem .8rem;backdrop-filter:blur(4px)}
  .hud b{color:#fff}
  .legend{position:absolute;right:12px;top:12px;z-index:20;background:#0007;border:1px solid #0ff3;
          border-radius:10px;padding:.6rem .8rem;font-size:12px}
  .legend kbd{background:#111;border:1px solid #333;border-bottom-width:2px;border-radius:6px;
              padding:.1rem .35rem;margin:0 .1rem;color:#ddd}
  .minimap{position:absolute;right:12px;bottom:12px;z-index:20;width:240px;height:160px;
           background:#05070d;border:1px solid #2c3e50;border-radius:12px;overflow:hidden}
  .minimap .title{font-size:11px;text-align:center;color:#9aa;padding:4px 0;border-bottom:1px solid #2c3e50}
  .minimap canvas{display:block;width:100%;height:calc(100% - 20px)}
  .toast{position:absolute;left:50%;transform:translateX(-50%);bottom:24px;z-index:25;background:#0b1320;
         border:1px solid #334;color:#cde;padding:.6rem .9rem;border-radius:10px;font-size:12px;opacity:.9}
</style>
</head>
<body>
<div class="wrap">
  <canvas id="view"></canvas>

  <div class="hud">
    <div>vel: <b id="vel">0</b></div>
    <div>rumo: <b id="hdg">0°</b></div>
    <div>pos: <b id="pos">0,0</b></div>
    <div>alvo: <b id="tgt">—</b></div>
  </div>

  <div class="legend">
    <div><b>Controles</b></div>
    <div><kbd>←</kbd>/<kbd>→</kbd> girar • <kbd>↑</kbd>/<kbd>↓</kbd> olhar</div>
    <div><kbd>A</kbd> acel. • <kbd>B</kbd> frear</div>
    <div><kbd>M</kbd> minimapa • <kbd>R</kbd> reset</div>
  </div>

  <div class="minimap" id="minimap">
    <div class="title">Mapa (100.000 × 100.000)</div>
    <canvas id="map"></canvas>
  </div>

  <div class="toast" id="toast" style="display:none"></div>
</div>

<script>
/* ======= Config do Mundo ======= */
const WORLD_W = 100000;
const WORLD_H = 100000; // quadrado 100k²
const FOV = 900;
const MAX_DRAW_DIST = 28000;
const STAR_COUNT = 800;
const IMG_PATH = "../images/";

/* ======= RNG com semente (troque pra re-randômico) ======= */
const SEED = 20251020; // mude este valor para outra distribuição
let _s = SEED >>> 0;
function rng(){ // xorshift32
  _s ^= _s << 13; _s ^= _s >>> 17; _s ^= _s << 5;
  return ((_s>>>0) / 4294967296);
}
function randRange(a,b){ return a + rng()*(b-a); }

/* ======= Catálogo (nomes, imagens, raios) ======= */
const bodyDefs = [
  ["Sol","sol.png",1200],
  ["Mercúrio","eros.png",120],
  ["Vênus","venus.png",350],
  ["Terra","terra.png",360],
  ["Marte","marte.png",270],
  ["Ceres","ceres.png",120],
  ["Júpiter","jupter.png",900],
  ["Ganimedes","ganimedes.png",260],
  ["Saturno","sartuno.png",800],
  ["Asteróide","asteroide.png",80],
  ["Netuno","netuno.png",520],
];

/* ======= Distribuição Aleatória ======= */
const MARGIN = 3000; // evita beirada
const MIN_GAP = 2000; // separação mínima entre centros
const bodies = [];
for(const [name,img,r] of bodyDefs){
  let tries = 0, placed = false;
  while(!placed && tries++ < 200){
    const x = randRange(MARGIN, WORLD_W-MARGIN);
    const y = randRange(MARGIN, WORLD_H-MARGIN);
    let ok = true;
    for(const b of bodies){
      const d = Math.hypot(b.x - x, b.y - y);
      if(d < (MIN_GAP + b.r + r)*0.6){ ok = false; break; }
    }
    if(ok){ bodies.push({name,img,x,y,r}); placed = true; }
  }
}

/* ======= Carrega imagens ======= */
const cache = {};
function loadImage(src){
  return new Promise(res=>{
    const im = new Image();
    im.src = IMG_PATH + src;
    im.onload = ()=>res(im);
    im.onerror = ()=>{ console.warn("Falha ao carregar", src); res(null); };
  });
}

/* ======= Nave/Câmera ======= */
const ship = {
  x: WORLD_W/2, y: WORLD_H/2,
  heading: 0, pitch: 0,
  vel: 0, acc: 0,
  maxSpeed: 1900, turnRate: 120
};

/* ======= Canvas/UI ======= */
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
let W=0,H=0,stars=[],showMap=true,lastT=0;

function makeStars(){
  stars = [];
  for(let i=0;i<STAR_COUNT;i++){
    stars.push({
      x: randRange(0, WORLD_W),
      y: randRange(0, WORLD_H),
      r: randRange(.4, 2.2),
      a: randRange(.2, .7)
    });
  }
}

function resize(){
  view.width = W = view.clientWidth;
  view.height = H = view.clientHeight;
  mapCanvas.width = minimapEl.clientWidth;
  mapCanvas.height = minimapEl.clientHeight - 20;
}
window.addEventListener('resize', resize);

/* ======= Controles ======= */
const keys = {};
window.addEventListener('keydown', e=>{
  keys[e.key.toLowerCase()] = true;
  if(["ArrowLeft","ArrowRight","ArrowUp","ArrowDown"].includes(e.key)) e.preventDefault();
  if(e.key.toLowerCase()==='m'){ showMap = !showMap; minimapEl.style.display = showMap?'block':'none'; }
  if(e.key.toLowerCase()==='r'){
    ship.x = WORLD_W/2; ship.y = WORLD_H/2;
    ship.heading = 0; ship.vel = 0;
    flash("Centralizado no quadrante 100k²");
  }
});
window.addEventListener('keyup', e=>{ keys[e.key.toLowerCase()] = false; });

function flash(msg){
  toast.textContent = msg;
  toast.style.display='block';
  clearTimeout(flash._t); flash._t = setTimeout(()=>toast.style.display='none', 1500);
}

/* ======= Simulação/Render ======= */
function clamp(v,a,b){ return Math.max(a, Math.min(b, v)); }
function deg2rad(d){ return d*Math.PI/180; }

function update(dt){
  if(keys['arrowleft'])  ship.heading -= ship.turnRate*dt;
  if(keys['arrowright']) ship.heading += ship.turnRate*dt;
  if(keys['arrowup'])    ship.pitch = clamp(ship.pitch - 40*dt, -20, 20);
  if(keys['arrowdown'])  ship.pitch = clamp(ship.pitch + 40*dt, -20, 20);
  ship.pitch *= (1-0.08);

  if(keys['a']) ship.acc = 1400;
  else if(keys['b']) ship.acc = -1000;
  else ship.acc = 0;

  ship.vel += ship.acc*dt;
  ship.vel = clamp(ship.vel, -ship.maxSpeed*0.5, ship.maxSpeed);
  ship.vel *= (1 - 0.15*dt);

  const r = deg2rad(ship.heading);
  ship.x += Math.cos(r)*ship.vel*dt;
  ship.y += Math.sin(r)*ship.vel*dt;

  ship.x = clamp(ship.x, 0, WORLD_W);
  ship.y = clamp(ship.y, 0, WORLD_H);
}

function draw(){
  // fundo simples (estrelas “2D” relativas ao mundo)
  ctx.fillStyle = '#000'; ctx.fillRect(0,0,W,H);
  ctx.save();
  // paralaxe discreta: usa posição da nave
  const sx = (ship.x / WORLD_W) * 2000;
  const sy = (ship.y / WORLD_H) * 2000;
  ctx.translate(-(sx%2000), -(sy%2000));
  for(const s of stars){
    ctx.globalAlpha = s.a; ctx.fillStyle = '#9ecbff';
    ctx.beginPath(); ctx.arc((s.x%2000), (s.y%2000), s.r, 0, Math.PI*2); ctx.fill();
  }
  ctx.restore(); ctx.globalAlpha = 1;

  // desenha corpos com escala por distância
  let nearestName = "—", nearestDist = Infinity;

  for(const b of bodies){
    const dx = b.x - ship.x;
    const dy = b.y - ship.y;
    const dist = Math.hypot(dx, dy);
    if(dist < nearestDist){ nearestDist = dist; nearestName = b.name; }
    if(dist > MAX_DRAW_DIST) continue;

    // ângulo relativo à proa
    const angTo = Math.atan2(dy, dx);
    let rel = angTo - deg2rad(ship.heading);
    rel = Math.atan2(Math.sin(rel), Math.cos(rel)); // normaliza [-π,π]

    const screenX = (W/2) + (rel * (W/Math.PI)); // -π→esq, +π→dir
    const screenY = H*0.6 + ship.pitch*3;

    const scale = clamp((FOV / Math.max(60, dist)), 0.02, 3.8);
    const rad = b.r * scale;

    const im = cache[b.img];
    if(im){
      ctx.save(); ctx.translate(screenX, screenY);
      const d = rad*2; ctx.drawImage(im, -d/2, -d/2, d, d); ctx.restore();
    }else{
      ctx.save(); ctx.translate(screenX, screenY);
      ctx.fillStyle = '#3f46d9'; ctx.beginPath(); ctx.arc(0,0, rad, 0, Math.PI*2); ctx.fill(); ctx.restore();
    }

    if(rad > 22){
      ctx.fillStyle = '#8aa2ff'; ctx.font = '12px system-ui, Arial'; ctx.textAlign='center';
      ctx.fillText(b.name, screenX, screenY - rad - 8);
    }
  }

  // retícula
  ctx.strokeStyle = '#5865f299';
  ctx.beginPath(); ctx.arc(W/2, H*0.6, 20, 0, Math.PI*2);
  ctx.moveTo(W/2-28, H*0.6); ctx.lineTo(W/2+28, H*0.6); ctx.stroke();

  // HUD
  hudVel.textContent = ship.vel.toFixed(0);
  hudHdg.textContent = (((ship.heading%360)+360)%360).toFixed(0)+'°';
  hudPos.textContent = `${ship.x.toFixed(0)},${ship.y.toFixed(0)}`;
  hudTgt.textContent = `${nearestName} (${nearestDist.toFixed(0)}px)`;
}

function drawMiniMap(){
  if(!showMap) return;
  const mW = mapCanvas.width, mH = mapCanvas.height;
  mapCtx.clearRect(0,0,mW,mH);
  mapCtx.fillStyle = '#0b1220'; mapCtx.fillRect(0,0,mW,mH);
  const sx = mW / WORLD_W, sy = mH / WORLD_H;

  // planetas
  for(const b of bodies){
    const x = b.x*sx, y = b.y*sy;
    mapCtx.fillStyle = '#3e50ff';
    mapCtx.beginPath(); mapCtx.arc(x, y, Math.max(1.3, b.r*sx*0.4), 0, Math.PI*2); mapCtx.fill();
  }

  // nave e rumo
  const nx = ship.x*sx, ny = ship.y*sy;
  mapCtx.fillStyle = '#ff5a5a';
  mapCtx.beginPath(); mapCtx.arc(nx, ny, 3.5, 0, Math.PI*2); mapCtx.fill();
  mapCtx.strokeStyle = '#ff8a8a';
  mapCtx.beginPath(); mapCtx.moveTo(nx, ny);
  const r = deg2rad(ship.heading);
  mapCtx.lineTo(nx + Math.cos(r)*18, ny + Math.sin(r)*18); mapCtx.stroke();
}

async function boot(){
  resize(); makeStars();
  await Promise.all(bodies.map(async b=>{
    const im = await loadImage(b.img); if(im) cache[b.img]=im;
  }));
  flash("Mundo 100k² liberado! A=acelere, B=freie, M=minimapa.");
  lastT = performance.now(); requestAnimationFrame(loop);
}

function loop(t){
  const dt = Math.min(0.05, (t - lastT)/1000); lastT = t;
  update(dt); draw(); drawMiniMap();
  requestAnimationFrame(loop);
}

boot();
</script>
</body>
</html>
