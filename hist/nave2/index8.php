<?php
// index.php — Sistema Solar 3D em um único arquivo (PHP + HTML + CSS + JS)
// Autor: ChatGPT p/ TIAGO — 2025-10-19
// Requisitos: manter as imagens nos paths fornecidos pelo usuário.
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Sistema Solar 3D — Órbitas e Navegação</title>
<style>
  :root{ --hud:#0ff; --fg:#e8f7ff; --ok:#36e26b; }
  html,body{margin:0;height:100%;background:#000;color:var(--fg);font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;overflow:hidden}
  canvas#view{position:fixed;inset:0;display:block;width:100%;height:100%}
  .hud{
    position:fixed;top:10px;left:10px;display:flex;gap:8px;flex-wrap:wrap;z-index:10;pointer-events:none
  }
  .panel{
    pointer-events:auto;background:rgba(0,0,0,.45);border:1px solid rgba(0,255,255,.35);
    padding:8px 10px;border-radius:10px;backdrop-filter:blur(4px);min-width:220px
  }
  .panel h3{margin:0 0 6px;color:var(--hud);font-size:14px;font-weight:700;letter-spacing:.3px}
  .kv{display:flex;gap:10px;flex-wrap:wrap;font-size:13px}
  .legend{font-size:12px;opacity:.85}
  #centerName{
    position:fixed;left:50%;top:10%;transform:translateX(-50%);text-align:center;
    color:#fff;font-weight:800;text-shadow:0 0 10px #000;font-size:min(22px,4vw);letter-spacing:.5px;z-index:9
  }
  #orbitMsg{
    position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);
    color:#fff;font-size:min(48px,8vw);text-shadow:0 0 10px #000,0 0 20px #000;z-index:9;pointer-events:none
  }
  .help{position:fixed;right:10px;bottom:10px;z-index:11}
  .help details{background:rgba(0,0,0,.45);border:1px solid rgba(0,255,255,.35);padding:8px 10px;border-radius:10px}
  .help summary{cursor:pointer;color:var(--hud);font-weight:700}
  .help kbd{background:#111;border:1px solid #333;border-bottom-color:#111;border-radius:4px;padding:2px 6px}
</style>
</head>
<body>
<canvas id="view"></canvas>

<div class="hud">
  <div class="panel">
    <h3>NAVE</h3>
    <div class="kv">
      <span><b>Vel</b>: <span id="hudVel">0</span> u/s</span>
      <span><b>Pos</b>: <span id="hudPos">0,0,0</span></span>
      <span><b>Yaw</b>: <span id="hudYaw">0°</span></span>
      <span><b>Pitch</b>: <span id="hudPitch">0°</span></span>
      <span><b>Roll</b>: <span id="hudRoll">0°</span></span>
    </div>
    <div class="legend">W/S acelera | A/D gira (yaw) | ←/→ roll | ↑/↓ pitch | R reset</div>
  </div>
  <div class="panel">
    <h3>ALVO</h3>
    <div class="kv">
      <span><b>Corpo</b>: <span id="hudTarget">—</span></span>
      <span><b>Dist</b>: <span id="hudDist">—</span> u</span>
    </div>
  </div>
</div>

<div id="centerName"></div>
<div id="orbitMsg"></div>

<div class="help">
  <details>
    <summary>Controles</summary>
    <div style="margin-top:6px;font-size:13px">
      <div><kbd>W</kbd>/<kbd>S</kbd> acelerar/frear</div>
      <div><kbd>A</kbd>/<kbd>D</kbd> girar esquerda/direita (yaw)</div>
      <div><kbd>↑</kbd>/<kbd>↓</kbd> inclinar (pitch)</div>
      <div><kbd>←</kbd>/<kbd>→</kbd> roll (giro lateral)</div>
      <div><kbd>R</kbd> resetar posição</div>
    </div>
  </details>
</div>

<script>
(function(){
  // ======== CONFIG ========
  const FOV = 900;               // campo de visão (maior = mais perto)
  const NEAR = 2;                // plano de corte próximo
  const ORBIT_FACTOR = 1.1;      // fator do raio p/ entrar em órbita visual
  const STAR_COUNT = 150;        // estrelas por frame (procedural leve)
  const BG_FADE = 0.2;           // opacidade de limpeza (rastro leve)

  // ======== CANVAS ========
  const canvas = document.getElementById('view');
  const ctx = canvas.getContext('2d');
  let W = 0, H = 0, DPR = 1;
  function resize(){
    DPR = Math.max(1, window.devicePixelRatio || 1);
    W = canvas.width  = Math.floor(window.innerWidth  * DPR);
    H = canvas.height = Math.floor(window.innerHeight * DPR);
    ctx.setTransform(DPR,0,0,DPR,0,0);
  }
  window.addEventListener('resize', resize, {passive:true});
  resize();

  // ======== INPUT ========
  const keys = {};
  function bindInput(){
    window.addEventListener('keydown', e => { keys[e.key.toLowerCase()] = true; });
    window.addEventListener('keyup',   e => { keys[e.key.toLowerCase()] = false; });
  }
  bindInput();

  // ======== HUD ELEMENTS ========
  const hudVel   = document.getElementById('hudVel');
  const hudPos   = document.getElementById('hudPos');
  const hudYaw   = document.getElementById('hudYaw');
  const hudPitch = document.getElementById('hudPitch');
  const hudRoll  = document.getElementById('hudRoll');
  const hudTarget= document.getElementById('hudTarget');
  const hudDist  = document.getElementById('hudDist');
  const centerNameEl = document.getElementById('centerName');
  const orbitMsgEl   = document.getElementById('orbitMsg');

  // ======== UTIL ========
  function clamp(v,a,b){ return Math.max(a, Math.min(b, v)); }
  function deg(v){ return (v*180/Math.PI); }
  function fmtAngle(rad){ let d = deg(rad)%360; if(d<0) d+=360; return d.toFixed(0)+'°'; }
  function dist3(a,b){ const dx=a.x-b.x, dy=a.y-b.y, dz=a.z-b.z; return Math.hypot(dx,dy,dz); }

  // ======== CARREGAMENTO DE IMAGENS ========
  const IMG = {
    asteroide:  "../images/asteroide.png",
    ceres:      "../images/ceres.png",
    eros:       "../images/eros.png",
    ganimedes:  "../images/ganimedes.png",
    jupter:     "../images/jupter.png",  // (sic)
    marte:      "../images/marte.png",
    sartuno:    "../images/sartuno.png", // (sic)
    sol:        "../images/sol.png",
    terra:      "../images/terra.png",
    universo:   "../images/universo.jpg",
    venus:      "../images/venus.png"
  };
  const imageCache = new Map();

  function makeFallbackDisk(color='#888'){
    const c = document.createElement('canvas'); c.width=c.height=256;
    const g = c.getContext('2d');
    g.fillStyle='#222'; g.fillRect(0,0,256,256);
    g.beginPath(); g.arc(128,128,110,0,Math.PI*2); g.fillStyle=color; g.fill();
    const im = new Image(); im.src = c.toDataURL(); return im;
  }

  function loadImage(src, fallbackColor='#888'){
    return new Promise(resolve=>{
      if(imageCache.has(src)) return resolve(imageCache.get(src));
      const im = new Image();
      im.onload = ()=>{ imageCache.set(src,im); resolve(im); };
      im.onerror = ()=>{ const fb = makeFallbackDisk(fallbackColor); imageCache.set(src,fb); resolve(fb); };
      im.src = src;
    });
  }

  async function preloadAll(planets){
    // carrega todas as imagens dos planetas + background universo (opcional)
    const tasks = [];
    for(const p of planets){ tasks.push(loadImage(p.img, p.fallback||'#777')); }
    tasks.push(loadImage(IMG.universo, '#000')); // bg
    await Promise.all(tasks);
  }

  // ======== CENÁRIO (PLANETAS EM 3D) ========
  // tamanho = diâmetro "visual" base (em unidades do mundo).
  const planets = [
    {name:"Sol",       x:   0,   y:   0,   z:   0,    size: 8000, img:IMG.sol,      fallback:'#ffdb70'},
    {name:"Mercúrio",  x: 9000,  y: 200,   z:-3000,   size:  400, img:IMG.eros,     fallback:'#a7a7a7'},
    {name:"Vênus",     x:15000,  y:-800,   z: 5000,   size:  950, img:IMG.venus,    fallback:'#ffcf75'},
    {name:"Terra",     x:25000,  y:1000,   z:   0,    size: 1000, img:IMG.terra,    fallback:'#66c4ff'},
    {name:"Marte",     x:35000,  y:-1200,  z:-4000,   size:  800, img:IMG.marte,    fallback:'#ff5533'},
    {name:"Ceres",     x:42000,  y: -100,  z:  800,   size:  300, img:IMG.ceres,    fallback:'#bbbbbb'},
    {name:"Eros",      x:47000,  y:  200,  z:-1200,   size:  240, img:IMG.eros,     fallback:'#aaaaaa'},
    {name:"Júpiter",   x:55000,  y: 3000,  z: 5000,   size: 3000, img:IMG.jupter,   fallback:'#e2b37c'},
    {name:"Ganimedes", x:56500,  y: 3100,  z: 5200,   size:  500, img:IMG.ganimedes,fallback:'#c9c9c9'},
    {name:"Saturno",   x:70000,  y:-4000,  z:-2000,   size: 2700, img:IMG.sartuno,  fallback:'#c6a16d'},
    {name:"Asteroide", x:76000,  y:  100,  z:  600,   size:  300, img:IMG.asteroide,fallback:'#888888'},
    {name:"Vênus 2",   x:82000,  y: -500,  z: -800,   size:  950, img:IMG.venus,    fallback:'#ffcf75'},
    {name:"Terra 2",   x:90000,  y:  300,  z:  200,   size: 1000, img:IMG.terra,    fallback:'#66c4ff'}
  ];

  // ======== NAVE ========
  const ship = {
    x: 1500, y: 0, z: 2000,
    yaw: 0, pitch: 0, roll: 0,
    vel: 0,
    accel: 90,
    friction: 0.992,
    maxVel: 2400
  };

  // ======== PROJEÇÃO 3D ========
  function rotateToCamera(dx,dy,dz){
    // aplica yaw, pitch, roll da nave para transformar o mundo em coords de câmera
    const cosy = Math.cos(ship.yaw),  siny = Math.sin(ship.yaw);
    const cosp = Math.cos(ship.pitch), sinp = Math.sin(ship.pitch);
    const cosr = Math.cos(ship.roll),  sinr = Math.sin(ship.roll);

    // yaw
    let X =  dx*cosy - dz*siny;
    let Z =  dx*siny + dz*cosy;
    let Y =  dy;

    // pitch
    const Y2 =  Y*cosp - Z*sinp;
    const Z2 =  Y*sinp + Z*cosp;
    Y = Y2; Z = Z2;

    // roll
    const X2 =  X*cosr - Y*sinr;
    const Y3 =  X*sinr + Y*cosr;
    X = X2; Y = Y3;

    return {X,Y,Z};
  }

  function project3D(wx,wy,wz){
    // ponto do mundo -> câmera -> tela
    const dx = wx - ship.x;
    const dy = wy - ship.y;
    const dz = wz - ship.z;
    const {X,Y,Z} = rotateToCamera(dx,dy,dz);
    if (Z <= NEAR) return null; // atrás da câmera ou muito perto

    const scale = FOV / Z;
    const sx = (W/DPR)*0.5 + X * scale;
    const sy = (H/DPR)*0.5 - Y * scale;
    return {sx,sy,scale,Z};
  }

  // ======== DESENHO ========
  function drawBackground(){
    // usa imagem universo (estática) + estrelas pseudo-aleatórias
    const bg = imageCache.get(IMG.universo);
    if (bg){
      // preencher tela com cover simples
      const cw = W/DPR, ch = H/DPR;
      const arImg = bg.width/bg.height || 1;
      const arScr = cw/ch;
      let dw, dh;
      if (arImg > arScr){ dh = ch; dw = dh*arImg; }
      else { dw = cw; dh = dw/arImg; }
      ctx.globalAlpha = 0.25;
      ctx.drawImage(bg, (cw-dw)/2, (ch-dh)/2, dw, dh);
      ctx.globalAlpha = 1;
    }

    // rastro/limpeza suave pro efeito de motion
    ctx.fillStyle = `rgba(0,0,0,${BG_FADE})`;
    ctx.fillRect(0,0,W/DPR,H/DPR);

    // estrelas
    ctx.globalAlpha = 0.7;
    ctx.fillStyle = '#8ff';
    const seed = ( ( (ship.x|0)*31 ^ (ship.z|0)*17 ) & 0xffff );
    for(let i=0;i<STAR_COUNT;i++){
      const x = ((i*73 + seed*13) % (W/DPR));
      const y = ((i*41 + seed*7 ) % (H/DPR));
      ctx.fillRect(x, y, 1, 1);
    }
    ctx.globalAlpha = 1;
  }

  function drawPlanetSprite(p){
    const proj = project3D(p.x,p.y,p.z);
    if (!proj) return null;

    const img = imageCache.get(p.img);
    const sizePx = p.size * proj.scale; // billboard
    if (sizePx < 1) return {name:p.name, Z:proj.Z}; // muito longe para render

    const w = sizePx, h = sizePx;
    const x = proj.sx - w/2, y = proj.sy - h/2;

    // textura
    if (img){
      ctx.imageSmoothingEnabled = true;
      ctx.drawImage(img, x, y, w, h);
    } else {
      // fallback: círculo
      ctx.beginPath();
      ctx.arc(proj.sx, proj.sy, sizePx/2, 0, Math.PI*2);
      ctx.fillStyle = p.fallback || '#888'; ctx.fill();
    }

    // label próximo
    if (proj.Z < 15000){
      ctx.font = '14px system-ui, Arial';
      ctx.textAlign = 'center';
      ctx.fillStyle = 'rgba(0,0,0,.6)';
      const textW = ctx.measureText(p.name).width + 12;
      ctx.fillRect(proj.sx - textW/2, y + h + 6, textW, 18);
      ctx.fillStyle = '#fff';
      ctx.fillText(p.name, proj.sx, y + h + 20);
    }

    return {name:p.name, Z:proj.Z};
  }

  function drawCrosshair(){
    const cx = (W/DPR)/2, cy = (H/DPR)/2;
    ctx.strokeStyle = 'rgba(0,255,255,.8)';
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.moveTo(cx-10,cy); ctx.lineTo(cx+10,cy);
    ctx.moveTo(cx,cy-10); ctx.lineTo(cx,cy+10);
    ctx.stroke();
  }

  // ======== LÓGICA ========
  function handleControls(dt){
    // orientação
    const yawSpeed   = 1.6 * dt;
    const pitchSpeed = 1.2 * dt;
    const rollSpeed  = 1.8 * dt;

    if (keys['a']) ship.yaw  -= yawSpeed;
    if (keys['d']) ship.yaw  += yawSpeed;
    if (keys['arrowup'])   ship.pitch -= pitchSpeed;
    if (keys['arrowdown']) ship.pitch += pitchSpeed;
    if (keys['arrowleft']) ship.roll  -= rollSpeed;
    if (keys['arrowright'])ship.roll  += rollSpeed;

    // aceleração
    if (keys['w']) ship.vel = clamp(ship.vel + ship.accel, -ship.maxVel, ship.maxVel);
    if (keys['s']) ship.vel = clamp(ship.vel - ship.accel, -ship.maxVel/2, ship.maxVel);

    ship.vel *= ship.friction;

    // reset
    if (keys['r']){
      ship.x=1500; ship.y=0; ship.z=2000;
      ship.yaw=0; ship.pitch=0; ship.roll=0;
      ship.vel=0;
      // Anti-repeat básico
      keys['r']=false;
    }
  }

  function moveShip(dt){
    // direção frente da nave considerando yaw/pitch (roll não afeta vetor frente)
    const dirX = Math.sin(ship.yaw) * Math.cos(ship.pitch);
    const dirY = Math.sin(ship.pitch);
    const dirZ = Math.cos(ship.yaw) * Math.cos(ship.pitch);

    ship.x += dirX * ship.vel * dt;
    ship.y += dirY * ship.vel * dt;
    ship.z += dirZ * ship.vel * dt;
  }

  function findNearest(){
    let best=null, bestD=Infinity;
    for(const p of planets){
      const d = dist3(ship, p);
      if (d<bestD){ bestD=d; best=p; }
    }
    return best ? {ref:best, dist:bestD} : null;
  }

  function updateHUD(nearest){
    hudVel.textContent = ship.vel.toFixed(0);
    hudPos.textContent = `${ship.x.toFixed(0)}, ${ship.y.toFixed(0)}, ${ship.z.toFixed(0)}`;
    hudYaw.textContent = fmtAngle(ship.yaw);
    hudPitch.textContent = fmtAngle(ship.pitch);
    hudRoll.textContent = fmtAngle(ship.roll);

    if (nearest){
      hudTarget.textContent = nearest.ref.name;
      hudDist.textContent = nearest.dist.toFixed(0);
      centerNameEl.textContent = nearest.dist < 8000 ? nearest.ref.name : '';
    } else {
      hudTarget.textContent = '—';
      hudDist.textContent = '—';
      centerNameEl.textContent = '';
    }
  }

  // ======== LOOP PRINCIPAL ========
  let last = performance.now();
  async function start(){
    await preloadAll(planets); // carrega texturas
    requestAnimationFrame(loop);
  }

  function loop(now){
    const dt = Math.min(0.05,(now-last)/1000);
    last = now;

    handleControls(dt);
    moveShip(dt);

    drawBackground();

    // desenhar planetas: ordenar por profundidade (Z projetado) para sobreposição
    const renderables = [];
    for(const p of planets){
      const proj = project3D(p.x,p.y,p.z);
      if (!proj) continue;
      renderables.push({p, Z:proj.Z});
    }
    renderables.sort((a,b)=> b.Z - a.Z); // do mais distante para o mais perto

    let nearestInfo = null;
    for(const r of renderables){
      const info = drawPlanetSprite(r.p);
      if (info){
        if (!nearestInfo || info.Z < nearestInfo.Z) nearestInfo = info;
      }
    }

    drawCrosshair();

    const nearest = findNearest();
    updateHUD(nearest);

    // mensagem de órbita (distância menor que ORBIT_FACTOR * raio visual base)
    orbitMsgEl.textContent = '';
    if (nearest){
      const orbitRadius = ORBIT_FACTOR * (nearest.ref.size*0.5);
      if (nearest.dist < orbitRadius){
        orbitMsgEl.textContent = `🪐 ÓRBITA DE ${nearest.ref.name.toUpperCase()}`;
      }
    }

    requestAnimationFrame(loop);
  }

  // ======== INIT ========
  start();

})();
</script>
</body>
</html>
