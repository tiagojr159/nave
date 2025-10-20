<?php
// index.php — Jogo de Nave em 5 camadas (HTML+CSS+JS em um arquivo)
// Mantendo tudo que já funcionava e adicionando: mundo linear 50.000u, planetas em linha (eixo Z),
// throttle com A/B, setas esquerda/direita giram, asteroides com rota contínua e explosão com som.
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1" />
<title>Nave 5 Camadas – Demo</title>
<style>
  :root { --hud: #0ff; --bg:#000; --accent:#1e90ff; --danger:#ff3b3b; }
  * { box-sizing: border-box; }
  html, body { margin:0; height:100%; background:#000; color:#eaeaea; font-family:system-ui, Arial, sans-serif; }
  .wrap { position:relative; width:100%; height:100dvh; overflow:hidden; background:#000; touch-action:none; }
  canvas.layer { position:absolute; inset:0; width:100%; height:100%; display:block; }
  #universe { z-index:10; }
  #planets  { z-index:20; }
  #others   { z-index:30; }
  #pvp      { z-index:25; } /* entre others e planets por estética */
  #shots    { z-index:40; }

  /* Controles UI (Camada 1) */
  .ui { position:absolute; inset:0; z-index:50; pointer-events:none; }
  .hud {
    position:absolute; left:12px; bottom:12px; right:12px; display:flex; gap:8px; align-items:flex-end; justify-content:space-between;
    pointer-events:none;
  }
  .panel {
    background:linear-gradient(180deg, rgba(20,20,20,.8), rgba(5,5,5,.8));
    border:1px solid rgba(0,255,255,.25);
    box-shadow:0 0 20px rgba(0,255,255,.1) inset;
    border-radius:14px; padding:10px 12px; min-width:220px; pointer-events:auto;
  }
  .gauge {
    width:140px; height:140px; border-radius:50%;
    border:4px solid rgba(0,255,255,.25); position:relative;
    background:radial-gradient(transparent 55%, rgba(0,255,255,.08) 56% 100%);
  }
  .gauge .needle { position:absolute; left:50%; top:50%; width:2px; height:60px; background:var(--hud); transform-origin:bottom; transform:translate(-50%, -100%) rotate(0deg); }
  .gauge .center { position:absolute; left:50%; top:50%; width:14px; height:14px; background:var(--hud); border-radius:50%; transform:translate(-50%, -50%); box-shadow:0 0 10px var(--hud); }
  .stat { font-size:12px; opacity:.85; }
  .row { display:flex; gap:8px; align-items:center; }
  .btn {
    pointer-events:auto; cursor:pointer; user-select:none;
    background:rgba(30,144,255,.15); border:1px solid rgba(30,144,255,.4);
    padding:10px 14px; border-radius:12px; font-weight:700; color:#cfeaff;
    backdrop-filter: blur(4px);
  }
  .btn:active { transform:scale(.98); }
  .legend { position:absolute; left:12px; top:12px; background:rgba(0,0,0,.35); border:1px solid rgba(255,255,255,.1); padding:8px 10px; border-radius:10px; font-size:12px; pointer-events:none;}
  .legend b { color:#fff; }
  .notice { position:absolute; right:12px; top:12px; background:rgba(30,144,255,.15); border:1px solid rgba(30,144,255,.35); padding:8px 10px; border-radius:10px; font-size:12px; pointer-events:none;}
  /* Joystick mobile */
  .stickWrap { position:absolute; left:14px; bottom:110px; width:120px; height:120px; border:2px dashed rgba(0,255,255,.3); border-radius:50%; pointer-events:auto; display:none; }
  .stick      { position:absolute; left:50%; top:50%; width:60px; height:60px; border-radius:50%; background:rgba(0,255,255,.2); border:2px solid rgba(0,255,255,.6); transform:translate(-50%,-50%); }
  .fireBtn { position:absolute; right:14px; bottom:120px; width:78px; height:78px; border-radius:50%; background:radial-gradient(circle at 30% 30%, rgba(255,0,0,.9), rgba(255,0,0,.5));
    border:2px solid rgba(255,255,255,.25); box-shadow:0 0 20px rgba(255,0,0,.5); pointer-events:auto; display:none; }
  @media (max-width: 900px) {
    .stickWrap, .fireBtn { display:block; }
    .hud .panel:first-child { display:none; } /* economiza espaço */
  }
  a, a:visited { color:#8bd3ff; text-decoration:none; }
</style>
</head>
<body>
<div class="wrap" id="wrap">
  <!-- CANVAS em camadas -->
  <canvas id="universe" class="layer"></canvas>
  <canvas id="planets"  class="layer"></canvas>
  <canvas id="pvp"      class="layer"></canvas>
  <canvas id="others"   class="layer"></canvas>
  <canvas id="shots"    class="layer"></canvas>

  <!-- UI / HUD (Camada 1) -->
  <div class="ui">
    <div class="legend">
      <div><b>Controles</b></div>
      <div>Arraste: girar câmera</div>
      <div>W/S: frente/trás &nbsp; A/D: strafe</div>
      <div>Q/E: subir/descer &nbsp; Shift: turbo</div>
      <div>↑/↓: zoom (aproximar/afastar) &nbsp; ←/→: girar</div>
      <div><b>A</b>: aumentar velocidade • <b>B</b>: diminuir velocidade</div>
      <div>Espaço / Botão: atirar 🔫</div>
    </div>
    <div class="notice">Universo linear 50.000u — planetas na mesma órbita; chegue perto para vê-los gigantes.</div>

    <div class="hud">
      <div class="panel">
        <div class="row" style="gap:12px">
          <div class="gauge" id="gauge">
            <div class="needle" id="needle"></div>
            <div class="center"></div>
          </div>
          <div>
            <div class="stat">Velocidade: <b id="spd">0</b></div>
            <div class="stat">Yaw/Pitch: <b id="ang">0 / 0</b></div>
            <div class="stat">Planeta alvo: <b id="pname">—</b></div>
            <div class="stat">Distância: <b id="pdist">—</b></div>
            <div class="stat">FPS: <b id="fps">—</b></div>
          </div>
        </div>
      </div>

      <div class="row" style="gap:10px">
        <button class="btn" id="btnCenter">Recentrar Câmera</button>
        <button class="btn" id="btnSpawn">Spawn Asteroide/Nave</button>
        <button class="btn" id="btnClear">Limpar Tiros</button>
      </div>
    </div>

    <!-- Controles móveis -->
    <div class="stickWrap" id="stickWrap">
      <div class="stick" id="stick"></div>
    </div>
    <div class="fireBtn" id="fireBtn" title="Atirar"></div>
  </div>
</div>

<script>
// ---------- Dimensão ----------
const wrap = document.getElementById('wrap');
const canvases = {
  universe: document.getElementById('universe'),
  planets : document.getElementById('planets'),
  pvp     : document.getElementById('pvp'),
  others  : document.getElementById('others'),
  shots   : document.getElementById('shots'),
};
const ctx = {};
function resize() {
  for (const k in canvases) {
    const c = canvases[k];
    c.width = wrap.clientWidth;
    c.height = wrap.clientHeight;
    ctx[k] = c.getContext('2d');
  }
}
window.addEventListener('resize', resize);
resize();

// ---------- Universo linear (50.000u) com wrap ----------
const WORLD_SIZE = 50000;
const HALF_WORLD = WORLD_SIZE/2;
function wrapCoord(v){ v = (v + HALF_WORLD) % WORLD_SIZE; if (v < 0) v += WORLD_SIZE; return v - HALF_WORLD; }
function shortestAxisDelta(a,b){ let d=a-b; if(d>HALF_WORLD)d-=WORLD_SIZE; if(d<-HALF_WORLD)d+=WORLD_SIZE; return d; }

// ---------- Assets (imagens Internet) ----------
const IMG = {
  // Universo - imagens largas (Unsplash, CORS ok)
  nebula: 'https://images.unsplash.com/photo-1447433819943-74a20887a81e?q=80&w=1920&auto=format&fit=crop',
  stars : 'https://images.unsplash.com/photo-1447433819943-74a20887a81e?q=80&w=2560&auto=format&fit=crop',
  // Planetas (mantendo os seus assets atuais)
  planetBlue: 'images/ganimedes.png',
  planetRed : 'images/ceres.png',
  // Nave/asteroide (PNGs com fundo transparente)
  ship: 'images/ceres.png',
  asteroid: 'images/asteroide.png',
};

// ---------- Áudio (laser + explosão) ----------
const LASER_URLS = ['media/laser1.mp3','media/laser2.mp3'];
const LASER_FALLBACK = 'data:audio/mp3;base64,//uQZAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAACcQAA...'; // truncado
const EXPLOSION_URLS = ['media/explosion.mp3','media/explosion2.mp3'];
const EXPLOSION_FALLBACK = 'data:audio/mp3;base64,//uQZAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAACcQAA...'; // truncado
let laserAudio = new Audio(); laserAudio.preload='auto';
let explosionAudio = new Audio(); explosionAudio.preload='auto';
(async () => {
  try { laserAudio.src = LASER_URLS[0]; await laserAudio.play().catch(()=>{}); laserAudio.pause(); laserAudio.currentTime = 0; }
  catch(e1){ try { laserAudio.src = LASER_URLS[1]; await laserAudio.play().catch(()=>{}); laserAudio.pause(); laserAudio.currentTime=0; } catch(e2){ laserAudio.src = LASER_FALLBACK; } }
  try { explosionAudio.src = EXPLOSION_URLS[0]; await explosionAudio.play().catch(()=>{}); explosionAudio.pause(); explosionAudio.currentTime = 0; }
  catch(e3){ try { explosionAudio.src = EXPLOSION_URLS[1]; await explosionAudio.play().catch(()=>{}); explosionAudio.pause(); explosionAudio.currentTime=0; } catch(e4){ explosionAudio.src = EXPLOSION_FALLBACK; } }
})();

// ---------- Estado do “mundo 3D” simplificado ----------
const state = {
  t: 0,
  pos: { x:0, y:0, z:-10000 },      // começa perto de um planeta
  vel: { x:0, y:0, z:0 },
  yaw: 0, pitch: 0,
  zoom: 1.0,
  throttle: 1.0,                    // << NOVO: velocidade base (A/B ajustam)
  speed: 0,
  bullets: [],
  entities: [],
  planets: [],
  explosions: [],
  mouse: { dragging:false, lx:0, ly:0 },
  fps: 0,
};

function rnd(a,b){ return a + Math.random()*(b-a); }
function clamp(v, a, b){ return Math.max(a, Math.min(b, v)); }

// Carrega imagens em objetos <img>
const loaded = {};
function loadImage(key, src) {
  return new Promise(res=>{
    const im = new Image();
    im.crossOrigin = 'anonymous';
    im.onload = ()=>res(loaded[key]=im);
    im.onerror = ()=>res(loaded[key]=null);
    im.src = src;
  });
}
Promise.all([
  loadImage('nebula', IMG.nebula),
  loadImage('stars',  IMG.stars),
  loadImage('planetBlue', IMG.planetBlue),
  loadImage('planetRed',  IMG.planetRed),
  loadImage('ship', IMG.ship),
  loadImage('asteroid', IMG.asteroid),
]).then(()=>init());

// ---------- Mundo inicial ----------
function init(){
  // Planetas alinhados no eixo Z (mesma "órbita" linear). Mantendo seus sprites atuais.
  // Distribuição ao longo de ~40k u para dar viagem longa. Todos em x=0,y=0.
  state.planets = [
    { name:'Helio', img:'planetBlue', x:0, y:0, z:-5000,  r:140 },
    { name:'Aqua',  img:'planetBlue', x:0, y:0, z:-10000, r:180 },
    { name:'Rubra', img:'planetRed',  x:0, y:0, z:-18000, r:230 },
    { name:'Crono', img:'planetBlue', x:0, y:0, z:-26000, r:200 },
    { name:'Nix',   img:'planetRed',  x:0, y:0, z:-34000, r:220 },
    { name:'Éter',  img:'planetBlue', x:0, y:0, z:-42000, r:160 },
  ];

  // Entidades iniciais (asteroides/naves) com rota contínua e aleatória
  for (let i=0;i<12;i++) spawnEntity();

  loop();
}

// ---------- Spawns ----------
function spawnEntity(){
  const isShip = Math.random()<0.35;
  const speed = rnd(0.2, 0.9);
  state.entities.push({
    kind: isShip?'ship':'asteroid',
    x: rnd(-200,200),
    y: rnd(-120,120),
    z: rnd(-HALF_WORLD,HALF_WORLD),
    vx: rnd(-0.6,0.6)*speed,
    vy: rnd(-0.4,0.4)*speed,
    vz: rnd(-0.8,0.8)*speed,
    r: isShip ? 38 : rnd(26,60),
    rot: rnd(0,Math.PI*2),
    rotSpd: rnd(-0.01,0.01),
    alive: true
  });
}

// ---------- Entrada: mouse/teclado/toque ----------
wrap.addEventListener('mousedown', e=>{ state.mouse.dragging=true; state.mouse.lx=e.clientX; state.mouse.ly=e.clientY; });
window.addEventListener('mouseup',   ()=> state.mouse.dragging=false);
window.addEventListener('mousemove', e=>{
  if(!state.mouse.dragging) return;
  const dx = e.clientX - state.mouse.lx;
  const dy = e.clientY - state.mouse.ly;
  state.yaw   += dx * 0.0025;
  state.pitch += dy * 0.0020;
  state.pitch = clamp(state.pitch, -Math.PI/2+0.05, Math.PI/2-0.05);
  state.mouse.lx = e.clientX; state.mouse.ly = e.clientY;
});

window.addEventListener('keydown', e=>{
  const turbo = e.shiftKey ? 2.5 : 1.0;
  const S = turbo * state.throttle; // << throttle global aplicado nos movimentos

  // Movimentos originais (preservados)
  if (e.code==='KeyW') state.vel.z -= 0.6*S;
  if (e.code==='KeyS') state.vel.z += 0.6*S;
  if (e.code==='KeyA') state.vel.x -= 0.6*S; // strafe preservado (e também acelera, ver abaixo)
  if (e.code==='KeyD') state.vel.x += 0.6*S;
  if (e.code==='KeyQ') state.vel.y -= 0.6*S;
  if (e.code==='KeyE') state.vel.y += 0.6*S;
  if (e.code==='Space') shoot();

  // NOVO: setas esquerda/direita giram (sensação 3D)
  if (e.code==='ArrowLeft')  state.yaw -= 0.05;
  if (e.code==='ArrowRight') state.yaw += 0.05;

  // Zoom manual (mantido como no seu código)
  if (e.code==='ArrowUp')   state.zoom = Math.min(2.5, state.zoom + 0.05);
  if (e.code==='ArrowDown') state.zoom = Math.max(0.5, state.zoom - 0.05);

  // NOVO: A/B ajustam throttle de velocidade SEM quebrar o strafe do A
  if (e.code==='KeyA') state.throttle = Math.min(3.0, state.throttle + 0.08); // acelerar
  if (e.code==='KeyB') state.throttle = Math.max(0.3, state.throttle - 0.08); // frear
});
window.addEventListener('keyup', e=>{
  if(['KeyW','KeyS'].includes(e.code)) state.vel.z *= 0.2;
  if(['KeyA','KeyD'].includes(e.code)) state.vel.x *= 0.2;
  if(['KeyQ','KeyE'].includes(e.code)) state.vel.y *= 0.2;
});

// Botões UI
document.getElementById('btnCenter').onclick = ()=>{ state.yaw=0; state.pitch=0; };
document.getElementById('btnSpawn').onclick = ()=> spawnEntity();
document.getElementById('btnClear').onclick = ()=> state.bullets.length=0;

// Mobile joystick & fire
const stickWrap = document.getElementById('stickWrap');
const stick     = document.getElementById('stick');
const fireBtn   = document.getElementById('fireBtn');

let joyActive=false, joyCx=0, joyCy=0;
function joyStart(x,y){
  joyActive=true; joyCx=x; joyCy=y;
  stickWrap.style.left = (x-60)+'px';
  stickWrap.style.top  = (y-60)+'px';
  stickWrap.style.display='block';
}
function joyMove(x,y){
  if(!joyActive) return;
  const dx = x-joyCx, dy = y-joyCy;
  const dist = Math.min(50, Math.hypot(dx,dy));
  const ang = Math.atan2(dy, dx);
  stick.style.transform = `translate(calc(-50% + ${Math.cos(ang)*dist}px), calc(-50% + ${Math.sin(ang)*dist}px))`;
  // Move (aplica throttle)
  const joyS = state.throttle;
  state.vel.x += Math.cos(ang) * (dist/50) * 0.8 * joyS;
  state.vel.z -= Math.sin(ang) * (dist/50) * 0.8 * joyS; // para cima do círculo = frente
}
function joyEnd(){ joyActive=false; state.vel.x*=0.1; state.vel.z*=0.1; stickWrap.style.display='none'; }
wrap.addEventListener('touchstart', e=>{ if (e.target===fireBtn) return; const t=e.touches[0]; joyStart(t.clientX, t.clientY); },{passive:true});
wrap.addEventListener('touchmove', e=>{ const t=e.touches[0]; joyMove(t.clientX, t.clientY); },{passive:true});
wrap.addEventListener('touchend', joyEnd);
fireBtn.addEventListener('touchstart', (e)=>{ e.preventDefault(); shoot(); });

// ---------- Disparos ----------
function shoot(){
  // som
  try { const a = laserAudio.cloneNode(); a.volume=0.6; a.play(); } catch(e){}
  // cria projétil
  const speed = 22 * state.throttle; // projétil um pouco mais rápido com throttle
  const dir = forwardVector();
  state.bullets.push({
    x: state.pos.x, y: state.pos.y, z: state.pos.z,
    vx: dir.x*speed, vy: dir.y*speed, vz: dir.z*speed,
    life: 120
  });
}

// Vetor “para frente” baseado em yaw/pitch
function forwardVector(){
  const cosP = Math.cos(state.pitch), sinP = Math.sin(state.pitch);
  const cosY = Math.cos(state.yaw),   sinY = Math.sin(state.yaw);
  return { x: sinY*cosP, y: sinP, z: -cosY*cosP };
}

// ---------- Projeção 3D -> 2D ----------
function project(x,y,z){
  // Move p/ espaço da câmera
  const cy = Math.cos(state.yaw), sy = Math.sin(state.yaw);
  const cp = Math.cos(state.pitch), sp = Math.sin(state.pitch);

  // Translate (com menor distância considerando wrap)
  let dx = shortestAxisDelta(x, state.pos.x) * -1;
  let dy = shortestAxisDelta(y, state.pos.y) * -1;
  let dz = shortestAxisDelta(z, state.pos.z) * -1;

  // Rot yaw
  let dz1 =  dz*cy - dx*sy;
  let dx1 =  dz*sy + dx*cy;
  // Rot pitch
  let dy1 =  dy*cp - dz1*sp;
  let dz2 =  dy*sp + dz1*cp;

  const fov = 700 * state.zoom; // distância focal com zoom
  if (dz2 > -10) return null; // atrás da câmera
  const sx = (dx1*fov)/(-dz2) + canvases.universe.width/2;
  const sy2 = (dy1*fov)/(-dz2) + canvases.universe.height/2;
  const scale = fov/(-dz2);
  return { x:sx, y:sy2, s:scale, dz:-dz2 };
}

// ---------- Render ----------
let lastTime = performance.now();
let frameCount=0, fpsTime=0;
function loop(){
  const now = performance.now(), dt = Math.min(33, now-lastTime)/16.666; // ~60fps base
  lastTime = now; state.t += dt;

  // Atualiza posição com wrap
  state.pos.x = wrapCoord(state.pos.x + state.vel.x * dt*6);
  state.pos.y = wrapCoord(state.pos.y + state.vel.y * dt*6);
  state.pos.z = wrapCoord(state.pos.z + state.vel.z * dt*6);
  // amortecimento
  state.vel.x *= 0.93; state.vel.y *= 0.93; state.vel.z *= 0.93;
  state.speed = Math.hypot(state.vel.x, state.vel.y, state.vel.z) * 10;

  // normaliza yaw (giro infinito sem overflow)
  state.yaw = (state.yaw + Math.PI*2) % (Math.PI*2);

  // Fundo Universo (parallax pela rotação)
  drawUniverse();

  // Planetas (escala por distância — efeito 3D aparente)
  drawPlanets();

  // Placeholder PVP (Camada 4)
  drawPVP();

  // Outras naves / asteroides (rota contínua aleatória)
  updateEntities(dt);
  drawEntities();

  // Tiros e colisões
  updateBullets(dt);
  drawBullets();

  // Explosões
  updateExplosions(dt);
  drawExplosions();

  // HUD
  updateHUD();

  requestAnimationFrame(loop);

  // FPS
  frameCount++; fpsTime += dt*16.666;
  if (fpsTime>=500){ document.getElementById('fps').textContent = Math.round(frameCount*1000/(fpsTime)); frameCount=0; fpsTime=0; }
}

// ---------- Fundo Universo ----------
function drawUniverse(){
  const c = ctx.universe, W = canvases.universe.width, H = canvases.universe.height;
  c.clearRect(0,0,W,H);

  // deslocamentos suaves conforme yaw/pitch
  const wrapYaw = ( (state.yaw % (Math.PI*2)) + Math.PI*2 ) % (Math.PI*2);
  const offX = (wrapYaw / (Math.PI*2)) * W;    // mapeia 0..2π -> 0..W
  const offY = (state.pitch) * 140;

  // desenha a nebulosa "repetindo" horizontalmente para emendar no 360°
  if (loaded.nebula) {
    const sx = -offX - W*0.2, sy = -H*0.2 - offY*0.5, sw = W*1.4, sh = H*1.4;
    c.drawImage(loaded.nebula, sx, sy, sw, sh);
    c.drawImage(loaded.nebula, sx + W, sy, sw, sh); // peça à direita para emenda
  }

  // estrelas por cima, também emendadas
  if (loaded.stars){
    c.globalAlpha=0.6;
    const sx2 = -offX - W*0.1, sy2 = -H*0.1 - offY, sw2 = W*1.2, sh2 = H*1.2;
    c.drawImage(loaded.stars, sx2, sy2, sw2, sh2);
    c.drawImage(loaded.stars, sx2 + W, sy2, sw2, sh2);
    c.globalAlpha=1;
  }

  // anéis estilizados
  c.strokeStyle = 'rgba(255,255,255,.06)';
  for(let r=120; r<W; r+=120){ c.beginPath(); c.arc(W*0.72, H*0.45, r, 0, Math.PI*2); c.stroke(); }
}

// ---------- Planetas ----------
function drawPlanets(){
  const c = ctx.planets, W=canvases.planets.width, H=canvases.planets.height;
  c.clearRect(0,0,W,H);
  let nearestName='—', nearestDist=Infinity;

  // desenhar do mais distante para o mais próximo (pintura correta)
  const order = [...state.planets].sort((a,b)=>{
    const da = Math.abs(shortestAxisDelta(state.pos.z,a.z));
    const db = Math.abs(shortestAxisDelta(state.pos.z,b.z));
    return db - da;
  });

  order.forEach(p=>{
    const pr = project(p.x, p.y, p.z);
    if (!pr) return;

    // Escala por proximidade (ajuste para ficar grande quando perto)
    const dzAbs = Math.abs(shortestAxisDelta(state.pos.z, p.z));
    const proximityScale = clamp(4000 / (dzAbs+1), 0.06, 1.2);
    const drawR = p.r * proximityScale;

    if (pr.dz < nearestDist){ nearestDist = pr.dz; nearestName = p.name; }

    c.save();
    c.globalAlpha = 0.98;

    // Em navegação linear, centraliza na tela com leve parallax do olhar
    const cx = W*0.5 + Math.sin(state.yaw)*W*0.12;
    const cy = H*0.5 + Math.sin(state.pitch)*H*0.08;

    if (loaded[p.img]){
      c.beginPath(); c.arc(cx, cy, drawR, 0, Math.PI*2); c.closePath();
      c.save(); c.clip();
      c.drawImage(loaded[p.img], cx-drawR, cy-drawR, drawR*2, drawR*2);
      c.restore();
      c.strokeStyle='rgba(0,255,255,.25)';
      c.lineWidth=2; c.stroke();
    } else {
      c.fillStyle='rgba(100,150,255,.3)';
      c.beginPath(); c.arc(cx,cy,drawR,0,Math.PI*2); c.fill();
      c.strokeStyle='rgba(0,200,255,.4)'; c.stroke();
    }

    // Label discreto quando não está gigante
    if (drawR < Math.min(W,H)*0.18) {
      c.fillStyle='#cfeaff'; c.font='12px monospace'; c.textAlign='center';
      c.fillText(p.name, cx, cy - drawR - 8);
    }
    c.restore();
  });

  document.getElementById('pname').textContent = nearestName;
  document.getElementById('pdist').textContent = nearestDist===Infinity?'—':nearestDist.toFixed(0)+' u';
}

// ---------- Placeholder “Outro Jogador” (Camada 4) ----------
function drawPVP(){
  const c = ctx.pvp, W=canvases.pvp.width, H=canvases.pvp.height;
  c.clearRect(0,0,W,H);
  c.save();
  c.globalAlpha=0.35;
  c.fillStyle='rgba(30,144,255,.25)';
  c.fillRect(W-210, 14, 196, 80);
  c.strokeStyle='rgba(30,144,255,.6)'; c.strokeRect(W-210,14,196,80);
  c.fillStyle='#cfeaff';
  c.font='12px monospace';
  c.fillText('Camada 4: Outro Jogador', W-200, 34);
  c.fillText('(reservado p/ PVP)', W-200, 52);
  c.restore();
}

// ---------- Entidades ----------
function updateEntities(dt){
  for (const e of state.entities){
    if(!e.alive) continue;
    e.x = wrapCoord(e.x + e.vx*dt*10);
    e.y = wrapCoord(e.y + e.vy*dt*10);
    e.z = wrapCoord(e.z + e.vz*dt*10);
    e.rot += e.rotSpd*dt*10;
    // drift aleatório suave e limite de velocidade
    e.vx += rnd(-0.004,0.004); e.vy += rnd(-0.003,0.003); e.vz += rnd(-0.004,0.004);
    const spd = Math.hypot(e.vx,e.vy,e.vz), maxSpd=1.2;
    if(spd>maxSpd){ e.vx*=maxSpd/spd; e.vy*=maxSpd/spd; e.vz*=maxSpd/spd; }
  }
}
function drawEntities(){
  const c = ctx.others, W=canvases.others.width, H=canvases.others.height;
  c.clearRect(0,0,W,H);
  for (const e of state.entities){
    if(!e.alive) continue;
    const pr = project(e.x, e.y, e.z);
    if (!pr) continue;
    const R = e.r * pr.s;
    c.save();
    c.translate(pr.x, pr.y);
    c.rotate(e.rot);
    if (e.kind==='ship' && loaded.ship){
      c.drawImage(loaded.ship, -R, -R, R*2, R*2);
    } else if (e.kind==='asteroid' && loaded.asteroid){
      c.drawImage(loaded.asteroid, -R, -R, R*2, R*2);
    } else {
      c.fillStyle='rgba(255,255,255,.6)';
      c.beginPath(); c.arc(0,0, R, 0, Math.PI*2); c.fill();
    }
    c.restore();
  }
}

// ---------- Tiros + colisões ----------
function updateBullets(dt){
  for (const b of state.bullets){
    b.x = wrapCoord(b.x + b.vx*dt);
    b.y = wrapCoord(b.y + b.vy*dt);
    b.z = wrapCoord(b.z + b.vz*dt);
    b.life -= dt;

    // colisão com asteroides
    for (const e of state.entities){
      if(!e.alive || e.kind!=='asteroid') continue;
      const dx=shortestAxisDelta(b.x,e.x), dy=shortestAxisDelta(b.y,e.y), dz=shortestAxisDelta(b.z,e.z);
      const dist=Math.hypot(dx,dy,dz);
      if(dist < e.r){ // HIT!
        e.alive=false;
        spawnExplosion(e.x,e.y,e.z, Math.max(30,e.r*1.6));
        try{ const ex = explosionAudio.cloneNode(); ex.volume=0.9; ex.play(); }catch(_){}
        b.life=0; break;
      }
    }
  }
  state.bullets = state.bullets.filter(b=>b.life>0);
}
function drawBullets(){
  const c = ctx.shots, W=canvases.shots.width, H=canvases.shots.height;
  c.clearRect(0,0,W,H);
  c.lineWidth = 2;
  for (const b of state.bullets){
    const pr = project(b.x,b.y,b.z);
    if (!pr) continue;
    c.strokeStyle = 'rgba(0,255,255,.9)';
    c.beginPath();
    c.arc(pr.x, pr.y, Math.max(1.5, 3*pr.s), 0, Math.PI*2);
    c.stroke();
  }
}

// ---------- Explosões (partículas simples) ----------
function spawnExplosion(x,y,z,size){
  for(let i=0;i<24;i++){
    const a1=rnd(0,Math.PI*2), a2=rnd(-Math.PI/2,Math.PI/2), spd=rnd(3,12);
    state.explosions.push({
      x,y,z,
      vx:Math.cos(a1)*Math.cos(a2)*spd,
      vy:Math.sin(a2)*spd*0.6,
      vz:Math.sin(a1)*Math.cos(a2)*spd,
      life:rnd(18,30),
      r:rnd(size*0.04,size*0.08),
      alpha:1
    });
  }
}
function updateExplosions(dt){
  for(const p of state.explosions){
    p.x = wrapCoord(p.x + p.vx*dt);
    p.y = wrapCoord(p.y + p.vy*dt);
    p.z = wrapCoord(p.z + p.vz*dt);
    p.life -= dt; p.alpha = Math.max(0, p.life/30);
  }
  state.explosions = state.explosions.filter(p=>p.life>0);
}
function drawExplosions(){
  const c=ctx.shots;
  for(const p of state.explosions){
    const pr=project(p.x,p.y,p.z); if(!pr) continue;
    c.save(); c.globalCompositeOperation='lighter';
    c.globalAlpha=p.alpha*0.9; c.fillStyle='#ffbf00';
    c.beginPath(); c.arc(pr.x,pr.y,Math.max(1.5,p.r*pr.s),0,Math.PI*2); c.fill();
    c.globalAlpha=p.alpha*0.6; c.fillStyle='#ff3b3b';
    c.beginPath(); c.arc(pr.x,pr.y,Math.max(1.0,p.r*0.6*pr.s),0,Math.PI*2); c.fill();
    c.restore();
  }
}

// ---------- HUD ----------
function updateHUD(){
  document.getElementById('spd').textContent = state.speed.toFixed(1);
  document.getElementById('ang').textContent = `${(state.yaw*57.3|0)}° / ${(state.pitch*57.3|0)}°`;
  const needle = document.getElementById('needle');
  needle.style.transform = `translate(-50%, -100%) rotate(${clamp(state.speed*3,0,240)}deg)`;
}

// ---------- Clique/Tap para atirar ----------
wrap.addEventListener('click', (e)=>{
  if (state.mouse.dragging) return;
  shoot();
});
</script>
</body>
</html>
