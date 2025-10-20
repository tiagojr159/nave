<?php
// index.php — Space Run: Sistema Solar 2D em "primeira pessoa" com componentes avançados
// Requisitos: apenas PHP (para entregar o arquivo), HTML, CSS e JavaScript puro.
// Controles: ← → giram a nave; ↑ ↓ ajustam a câmera; A acelera; B freia/inverte; R recentra; M mostra/oculta minimapa.
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Space Run — Sistema Solar com Componentes Avançados</title>
<style>
  :root{
    --bg:#000; --hud:#8be9fd; --accent:#5865f2; --danger:#ff5555; --ok:#50fa7b;
    --hologram:#9d4edd; --waypoint:#f72585; --planet:#4cc9f0;
  }
  *{box-sizing:border-box}
  html,body{height:100%;margin:0;background:#000;font-family:system-ui,Segoe UI,Arial,sans-serif;color:#eaeaea}
  .wrap{position:relative;width:100%;height:100%;overflow:hidden;background:#000}
  canvas#view{position:absolute;inset:0;width:100%;height:100%;display:block;background:#000}
  
  /* HUD Otimizado */
  .hud{
    position:absolute;left:12px;top:12px;z-index:20;display:flex;flex-direction:column;gap:.3rem;
    font-size:12px;color:var(--hud);text-shadow:0 0 6px #0ff4;
    background:linear-gradient(135deg, rgba(0,20,40,0.85), rgba(0,10,30,0.9));
    border:1px solid #0ff5;border-radius:10px;padding:.8rem;backdrop-filter:blur(8px);
    box-shadow:0 0 15px rgba(0,255,255,0.3);width:200px;
  }
  .hud-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:3px 0; border-bottom:1px solid rgba(0,255,255,0.2);
  }
  .hud-row:last-child { border-bottom:none; }
  .hud b{color:#fff;font-weight:bold}
  
  /* Controles */
  .legend{
    position:absolute;left:12px;top:170px;z-index:20;
    background:linear-gradient(135deg, rgba(0,20,40,0.85), rgba(0,10,30,0.9));
    border:1px solid #0ff3;border-radius:10px;padding:.6rem .8rem;font-size:12px;
    box-shadow:0 0 15px rgba(0,255,255,0.2);width:200px;
  }
  .legend kbd{background:#111;border:1px solid #333;border-bottom-width:2px;border-radius:6px;padding:.1rem .35rem;margin:0 .1rem;color:#ddd}
  
  /* Minimapa Otimizado */
  .minimap{
    position:absolute;right:12px;bottom:180px;z-index:20;width:220px;height:140px;
    background:linear-gradient(135deg, rgba(5,15,30,0.9), rgba(10,25,45,0.85));
    border:2px solid #2c3e50;border-radius:12px;overflow:hidden;
    box-shadow:0 0 24px rgba(0,100,200,0.4) inset, 0 0 15px rgba(0,100,200,0.3);
  }
  .minimap .title{font-size:11px;text-align:center;color:#9aa; padding:4px 0;border-bottom:1px solid #2c3e50}
  .minimap canvas{display:block;width:100%;height:calc(100% - 20px)}
  
  /* Mapa Holográfico Reposicionado */
  .hologram-container {
    position:absolute;right:12px;bottom:12px;z-index:20;
    background:linear-gradient(135deg, rgba(5,15,30,0.9), rgba(10,25,45,0.85));
    border:3px solid rgba(157,78,221,0.8);border-radius:12px;padding:12px;
    width:220px;height:160px;box-shadow:0 0 30px rgba(157,78,221,0.5);
    perspective:1000px;
  }
  .hologram-title {
    font-weight:bold;margin-bottom:8px;color:var(--hologram);
    font-size:14px;text-align:center;text-shadow:0 0 10px rgba(157,78,221,0.7);
  }
  #hologram {
    border-radius:8px;background:rgba(10,0,30,0.9);
    border:1px solid rgba(157,78,221,0.5);
    width:196px;height:120px;
  }
  
  /* Scanner de Proximidade Reposicionado */
  .scanner-container {
    position:absolute;right:12px;top:12px;z-index:20;
    background:linear-gradient(135deg, rgba(5,15,30,0.9), rgba(10,25,45,0.85));
    border:2px solid rgba(247,37,133,0.8);border-radius:12px;
    width:120px;height:150px;padding:10px 5px;
    box-shadow:0 0 15px rgba(247,37,133,0.3);
  }
  .scanner-title {
    text-align:center;font-weight:bold;color:var(--waypoint);
    font-size:12px;margin-bottom:5px;
  }
  .scanner {
    position:relative;width:110px;height:110px;
    border-radius:50%;background:radial-gradient(circle, rgba(30,30,30,0.8), rgba(10,10,10,0.9));
    border:1px solid rgba(247,37,133,0.5);overflow:hidden;margin:0 auto;
  }
  .scanner-sweep {
    position:absolute;top:50%;left:50%;width:2px;height:45px;
    background:linear-gradient(to top, transparent, rgba(247,37,133,0.9));
    transform-origin:bottom center;transform:translate(-50%, -100%) rotate(0deg);
    border-radius:1px;box-shadow:0 0 10px rgba(247,37,133,0.7);
    animation:scan 4s linear infinite;
  }
  @keyframes scan {
    0% { transform: translate(-50%, -100%) rotate(0deg); }
    100% { transform: translate(-50%, -100%) rotate(360deg); }
  }
  .scanner-center {
    position:absolute;top:50%;left:50%;width:12px;height:12px;
    background:var(--waypoint);border-radius:50%;
    transform:translate(-50%, -50%);box-shadow:0 0 8px rgba(247,37,133,0.8);
  }
  .scanner-object {
    position:absolute;width:6px;height:6px;border-radius:50%;
    background:rgba(0,255,255,0.9);box-shadow:0 0 5px rgba(0,255,255,0.7);
  }
  
  /* Navegação por Waypoints Reposicionada */
  .waypoint-container {
    position:absolute;right:12px;top:170px;z-index:20;
    background:linear-gradient(135deg, rgba(5,15,30,0.9), rgba(10,25,45,0.85));
    border:1px solid rgba(247,37,133,0.3);border-radius:10px;
    padding:10px;width:220px;box-shadow:0 0 15px rgba(247,37,133,0.2);
  }
  .waypoint-title {
    font-weight:bold;margin-bottom:8px;color:var(--waypoint);
    text-align:center;font-size:14px;
  }
  .waypoint-item {
    display:flex;justify-content:space-between;padding:5px 0;
    border-bottom:1px solid rgba(255,255,255,0.1);cursor:pointer;
    transition:background 0.2s;
  }
  .waypoint-item:hover {
    background:rgba(247,37,133,0.2);border-radius:5px;padding-left:5px;
  }
  .waypoint-item.active {
    background:rgba(247,37,133,0.3);border-radius:5px;padding-left:5px;font-weight:bold;
  }
  .waypoint-distance {
    color:#aaa;font-size:11px;
  }
  
  /* Novo Painel de Visão Planetária */
  .planet-view {
    position:absolute;left:12px;bottom:12px;z-index:20;
    background:linear-gradient(135deg, rgba(5,15,30,0.9), rgba(10,25,45,0.85));
    border:2px solid var(--planet);border-radius:12px;padding:12px;
    width:250px;box-shadow:0 0 20px rgba(76,201,240,0.4);
  }
  .planet-title {
    font-weight:bold;margin-bottom:10px;color:var(--planet);
    font-size:16px;text-align:center;text-shadow:0 0 10px rgba(76,201,240,0.7);
  }
  .planet-info {
    display:flex;flex-direction:column;gap:8px;
  }
  .planet-info-row {
    display:flex;justify-content:space-between;
    padding:4px 0;border-bottom:1px solid rgba(76,201,240,0.2);
  }
  .planet-info-label {
    color:#aaa;font-size:12px;
  }
  .planet-info-value {
    color:#fff;font-weight:bold;font-size:12px;
  }
  .planet-image-container {
    width:80px;height:80px;margin:10px auto;
    border-radius:50%;overflow:hidden;border:2px solid var(--planet);
    box-shadow:0 0 15px rgba(76,201,240,0.5);
  }
  .planet-image {
    width:100%;height:100%;object-fit:cover;
  }
  .planet-proximity {
    height:8px;background:rgba(255,255,255,0.1);border-radius:4px;
    margin-top:8px;overflow:hidden;
  }
  .planet-proximity-bar {
    height:100%;background:linear-gradient(90deg, var(--ok), var(--danger));
    width:30%;border-radius:4px;
  }
  
  /* Controles de Navegação Simplificados */
  .controls-container {
    position:absolute;bottom:30px;left:50%;transform:translateX(-50%);
    display:flex;gap:15px;z-index:60;
  }
  .control-btn {
    width:70px;height:70px;border-radius:50%;
    background:linear-gradient(135deg, rgba(30,144,255,0.3), rgba(20,100,200,0.2));
    border:2px solid rgba(30,144,255,0.6);display:flex;
    align-items:center;justify-content:center;font-size:28px;
    color:#fff;cursor:pointer;transition:all 0.2s;
    box-shadow:0 0 15px rgba(30,144,255,0.3);
  }
  .control-btn:hover {
    background:linear-gradient(135deg, rgba(30,144,255,0.5), rgba(20,100,200,0.3));
    transform:scale(1.05);
  }
  .control-btn:active {
    transform:scale(0.95);
  }
  
  /* Painel de Configurações */
  .settings-container {
    position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);
    background:linear-gradient(135deg, rgba(5,15,30,0.95), rgba(10,25,45,0.9));
    border:2px solid rgba(157,78,221,0.8);border-radius:16px;
    padding:20px;width:400px;max-height:80vh;overflow-y:auto;
    z-index:100;display:none;box-shadow:0 0 30px rgba(157,78,221,0.5);
  }
  .settings-container.active {
    display:block;
  }
  .settings-title {
    font-size:20px;font-weight:bold;color:var(--hologram);
    margin-bottom:20px;text-align:center;
    text-shadow:0 0 10px rgba(157,78,221,0.7);
  }
  .settings-group {
    margin-bottom:20px;
  }
  .settings-group-title {
    font-size:16px;font-weight:bold;color:#fff;
    margin-bottom:10px;border-bottom:1px solid rgba(157,78,221,0.3);
    padding-bottom:5px;
  }
  .setting-item {
    display:flex;justify-content:space-between;align-items:center;
    margin-bottom:12px;padding:8px;
    background:rgba(30,30,30,0.5);border-radius:8px;
  }
  .setting-label {
    font-size:14px;color:#eaeaea;
  }
  .setting-control {
    display:flex;align-items:center;
  }
  .slider {
    width:120px;height:6px;background:rgba(157,78,221,0.3);
    border-radius:3px;outline:none;-webkit-appearance:none;
  }
  .slider::-webkit-slider-thumb {
    -webkit-appearance:none;width:16px;height:16px;
    background:var(--hologram);border-radius:50%;cursor:pointer;
  }
  .slider::-moz-range-thumb {
    width:16px;height:16px;background:var(--hologram);
    border-radius:50%;cursor:pointer;border:none;
  }
  .toggle {
    position:relative;width:50px;height:24px;
    background:rgba(157,78,221,0.3);border-radius:12px;cursor:pointer;
  }
  .toggle.active {
    background:var(--hologram);
  }
  .toggle-slider {
    position:absolute;top:2px;left:2px;width:20px;height:20px;
    background:#fff;border-radius:50%;transition:transform 0.2s;
  }
  .toggle.active .toggle-slider {
    transform:translateX(26px);
  }
  .settings-buttons {
    display:flex;justify-content:space-between;margin-top:20px;
  }
  .settings-btn {
    padding:10px 20px;background:rgba(30,144,255,0.2);
    border:1px solid rgba(30,144,255,0.5);border-radius:8px;
    color:#fff;cursor:pointer;font-weight:bold;transition:all 0.2s;
  }
  .settings-btn:hover {
    background:rgba(30,144,255,0.4);
  }
  .settings-btn.primary {
    background:rgba(157,78,221,0.3);border-color:var(--hologram);
  }
  .settings-btn.primary:hover {
    background:rgba(157,78,221,0.5);
  }
  
  /* Feedback Visual */
  .feedback {
    position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);
    font-size:18px;font-weight:bold;color:#fff;
    text-shadow:0 0 10px rgba(0,255,255,0.8);
    pointer-events:none;z-index:70;opacity:0;transition:opacity 0.3s;
  }
  
  /* Efeito de Velocidade */
  .speed-effect {
    position:absolute;top:0;left:0;width:100%;height:100%;
    pointer-events:none;z-index:5;opacity:0;
    background:radial-gradient(circle at center, transparent 30%, rgba(0,100,255,0.1) 70%);
    transition:opacity 0.3s;
  }
  
  .toast {
    position:absolute;left:50%;transform:translateX(-50%);bottom:24px;z-index:25;
    background:linear-gradient(135deg, rgba(10,20,40,0.9), rgba(5,15,35,0.8));
    border:1px solid #334;color:#cde;padding:.6rem .9rem;
    border-radius:10px;font-size:12px;opacity:.85;
    box-shadow:0 0 15px rgba(0,100,200,0.3);
  }
  
  @media (max-width: 900px) {
    .hud { width:160px; padding:.6rem; }
    .legend { width:160px; font-size:11px; }
    .hologram-container { width:180px; height:130px; }
    #hologram { width:156px; height:90px; }
    .scanner-container { width:100px; height:130px; }
    .scanner { width:90px; height:90px; }
    .scanner-sweep { height:35px; }
    .waypoint-container { width:180px; }
    .planet-view { width:200px; }
    .controls-container { bottom:20px; }
    .control-btn { width:60px; height:60px; font-size:24px; }
    .settings-container { width:90%; max-width:350px; }
  }
</style>
</head>
<body>
<div class="wrap">
  <canvas id="view"></canvas>
  
  <!-- Efeito de Velocidade -->
  <div class="speed-effect" id="speedEffect"></div>
  
  <!-- Feedback Visual -->
  <div class="feedback" id="feedback"></div>
  
  <!-- HUD Otimizado -->
  <div class="hud" id="hud">
    <div class="hud-row">
      <span>vel:</span>
      <b id="vel">0</b>
    </div>
    <div class="hud-row">
      <span>rumo:</span>
      <b id="hdg">0°</b>
    </div>
    <div class="hud-row">
      <span>pos:</span>
      <b id="pos">0,0</b>
    </div>
    <div class="hud-row">
      <span>alvo:</span>
      <b id="tgt">—</b>
    </div>
    <div class="hud-row">
      <span>energia:</span>
      <b id="energy">100%</b>
    </div>
  </div>

  <div class="legend">
    <div style="font-weight:bold; margin-bottom:5px; text-align:center;">CONTROLES</div>
    <div><kbd>←</kbd>/<kbd>→</kbd> girar • <kbd>↑</kbd>/<kbd>↓</kbd> olhar</div>
    <div><kbd>A</kbd> acelerar • <kbd>B</kbd> frear</div>
    <div><kbd>M</kbd> minimapa • <kbd>R</kbd> reset</div>
    <div><kbd>ESC</kbd> configurações</div>
  </div>
  
  <!-- Scanner de Proximidade Reposicionado -->
  <div class="scanner-container">
    <div class="scanner-title">SCANNER</div>
    <div class="scanner" id="scanner">
      <div class="scanner-sweep"></div>
      <div class="scanner-center"></div>
    </div>
  </div>
  
  <!-- Navegação por Waypoints Reposicionada -->
  <div class="waypoint-container">
    <div class="waypoint-title">WAYPOINTS</div>
    <div id="waypointNav"></div>
  </div>
  
  <!-- Minimapa Otimizado -->
  <div class="minimap" id="minimap">
    <div class="title">Mapa (100.000 × 100.000 px)</div>
    <canvas id="map"></canvas>
  </div>
  
  <!-- Mapa Holográfico Reposicionado -->
  <div class="hologram-container">
    <div class="hologram-title">🌌 MAPA HOLOGRÁFICO 🌌</div>
    <canvas id="hologram"></canvas>
  </div>
  
  <!-- Novo Painel de Visão Planetária -->
  <div class="planet-view" id="planetView">
    <div class="planet-title">VISÃO PLANETÁRIA</div>
    <div class="planet-image-container">
      <img class="planet-image" id="planetImage" src="" alt="Planeta">
    </div>
    <div class="planet-info">
      <div class="planet-info-row">
        <span class="planet-info-label">Nome:</span>
        <span class="planet-info-value" id="planetName">—</span>
      </div>
      <div class="planet-info-row">
        <span class="planet-info-label">Distância:</span>
        <span class="planet-info-value" id="planetDistance">—</span>
      </div>
      <div class="planet-info-row">
        <span class="planet-info-label">Diâmetro:</span>
        <span class="planet-info-value" id="planetDiameter">—</span>
      </div>
      <div class="planet-info-row">
        <span class="planet-info-label">Temperatura:</span>
        <span class="planet-info-value" id="planetTemp">—</span>
      </div>
      <div class="planet-info-row">
        <span class="planet-info-label">Atmosfera:</span>
        <span class="planet-info-value" id="planetAtmosphere">—</span>
      </div>
    </div>
    <div class="planet-proximity">
      <div class="planet-proximity-bar" id="planetProximityBar"></div>
    </div>
  </div>
  
  <!-- Controles de Navegação Simplificados -->
  <div class="controls-container">
    <div class="control-btn" id="brakeBtn" title="Frear (B)">⏹️</div>
    <div class="control-btn" id="boostBtn" title="Turbo (Shift)">⚡</div>
  </div>
  
  <!-- Painel de Configurações -->
  <div class="settings-container" id="settingsPanel">
    <div class="settings-title">⚙️ CONFIGURAÇÕES</div>
    
    <div class="settings-group">
      <div class="settings-group-title">🎮 Controles</div>
      
      <div class="setting-item">
        <div class="setting-label">Sensibilidade do Mouse</div>
        <div class="setting-control">
          <input type="range" class="slider" id="mouseSensitivity" min="0.01" max="0.2" step="0.01" value="0.05">
        </div>
      </div>
      
      <div class="setting-item">
        <div class="setting-label">Inverter Eixo Y</div>
        <div class="setting-control">
          <div class="toggle" id="invertY">
            <div class="toggle-slider"></div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="settings-group">
      <div class="settings-group-title">🔊 Áudio</div>
      
      <div class="setting-item">
        <div class="setting-label">Volume dos Efeitos</div>
        <div class="setting-control">
          <input type="range" class="slider" id="sfxVolume" min="0" max="1" step="0.1" value="0.6">
        </div>
      </div>
      
      <div class="setting-item">
        <div class="setting-label">Ativar Som</div>
        <div class="setting-control">
          <div class="toggle active" id="soundEnabled">
            <div class="toggle-slider"></div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="settings-group">
      <div class="settings-group-title">🎨 Gráficos</div>
      
      <div class="setting-item">
        <div class="setting-label">Qualidade Gráfica</div>
        <div class="setting-control">
          <select class="select" id="graphicsQuality">
            <option value="low">Baixa</option>
            <option value="medium" selected>Média</option>
            <option value="high">Alta</option>
          </select>
        </div>
      </div>
      
      <div class="setting-item">
        <div class="setting-label">Mostrar Holograma</div>
        <div class="setting-control">
          <div class="toggle active" id="showHologram">
            <div class="toggle-slider"></div>
          </div>
        </div>
      </div>
      
      <div class="setting-item">
        <div class="setting-label">Mostrar Scanner</div>
        <div class="setting-control">
          <div class="toggle active" id="showScanner">
            <div class="toggle-slider"></div>
          </div>
        </div>
      </div>
      
      <div class="setting-item">
        <div class="setting-label">Mostrar Waypoints</div>
        <div class="setting-control">
          <div class="toggle active" id="showWaypoints">
            <div class="toggle-slider"></div>
          </div>
        </div>
      </div>
      
      <div class="setting-item">
        <div class="setting-label">Mostrar Visão Planetária</div>
        <div class="setting-control">
          <div class="toggle active" id="showPlanetView">
            <div class="toggle-slider"></div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="settings-buttons">
      <button class="settings-btn" id="resetSettings">Redefinir</button>
      <button class="settings-btn primary" id="saveSettings">Salvar</button>
    </div>
  </div>
  
  <div class="toast" id="toast" style="display:none"></div>
</div>

<script>
/* ========= Config do Mundo ========= */
const WORLD_W = 100000;      // largura virtual do "setor"
const WORLD_H = 100000;      // altura virtual — agora você pode voar o quadradão inteiro
const FOV = 900;             // campo de visão para "escala por distância"
const MAX_DRAW_DIST = 20000; // recorte de renderização (desempenho)
const STAR_COUNT = 400;      // estrelas na esfera celeste
const IMG_PATH = "../images/"; // base das imagens fornecidas

// Catálogo (raios/nomes/imagens fixos; posições serão aleatórias)
const bodies = [
  {name:"Sol",        img:"sol.png",        x:0, y:0, r:1200, temp:"5.778 K", atmosphere:"Hélio, Hidrogênio"},
  {name:"Mercúrio",   img:"eros.png",       x:0, y:0, r:120, temp:"440 K", atmosphere:"Exosfera"},
  {name:"Vênus",      img:"venus.png",      x:0, y:0, r:350, temp:"735 K", atmosphere:"Dióxido de Carbono"},
  {name:"Terra",      img:"terra.png",      x:0, y:0, r:360, temp:"288 K", atmosphere:"Nitrogênio, Oxigênio"},
  {name:"Marte",      img:"marte.png",      x:0, y:0, r:270, temp:"210 K", atmosphere:"Dióxido de Carbono"},
  {name:"Ceres",      img:"ceres.png",      x:0, y:0, r:120, temp:"200 K", atmosphere:"Sem atmosfera"},
  {name:"Júpiter",    img:"jupter.png",     x:0, y:0, r:900, temp:"165 K", atmosphere:"Hidrogênio, Hélio"},
  {name:"Ganimedes",  img:"ganimedes.png",  x:0, y:0, r:260, temp:"110 K", atmosphere:"Oxigênio"},
  {name:"Saturno",    img:"sartuno.png",    x:0, y:0, r:800, temp:"134 K", atmosphere:"Hidrogênio, Hélio"},
  {name:"Asteróide",  img:"asteroide.png",  x:0, y:0, r:80, temp:"173 K", atmosphere:"Sem atmosfera"},
  {name:"Netuno",     img:"netuno.png",     x:0, y:0, r:520, temp:"72 K", atmosphere:"Hidrogênio, Hélio"},
];

// Sistema de Configurações
const settings = {
  // Controles
  mouseSensitivity: 0.05,
  invertY: false,
  
  // Áudio
  sfxVolume: 0.6,
  soundEnabled: true,
  
  // Gráficos
  graphicsQuality: 'medium',
  showHologram: true,
  showScanner: true,
  showWaypoints: true,
  showPlanetView: true
};

// Carregar configurações salvas
function loadSettings() {
  const savedSettings = localStorage.getItem('spaceGameSettings');
  if (savedSettings) {
    try {
      const parsed = JSON.parse(savedSettings);
      Object.assign(settings, parsed);
      
      // Aplicar configurações à UI
      document.getElementById('mouseSensitivity').value = settings.mouseSensitivity;
      document.getElementById('sfxVolume').value = settings.sfxVolume;
      document.getElementById('graphicsQuality').value = settings.graphicsQuality;
      
      // Configurar toggles
      setToggleState('invertY', settings.invertY);
      setToggleState('soundEnabled', settings.soundEnabled);
      setToggleState('showHologram', settings.showHologram);
      setToggleState('showScanner', settings.showScanner);
      setToggleState('showWaypoints', settings.showWaypoints);
      setToggleState('showPlanetView', settings.showPlanetView);
      
      // Aplicar configurações visuais
      applyVisualSettings();
    } catch (e) {
      console.error('Erro ao carregar configurações:', e);
    }
  }
}

// Salvar configurações
function saveSettings() {
  try {
    localStorage.setItem('spaceGameSettings', JSON.stringify(settings));
    showFeedback('Configurações salvas com sucesso!');
  } catch (e) {
    console.error('Erro ao salvar configurações:', e);
    showFeedback('Erro ao salvar configurações');
  }
}

// Redefinir configurações
function resetSettings() {
  // Redefinir para valores padrão
  settings.mouseSensitivity = 0.05;
  settings.invertY = false;
  settings.sfxVolume = 0.6;
  settings.soundEnabled = true;
  settings.graphicsQuality = 'medium';
  settings.showHologram = true;
  settings.showScanner = true;
  settings.showWaypoints = true;
  settings.showPlanetView = true;
  
  // Atualizar UI
  document.getElementById('mouseSensitivity').value = settings.mouseSensitivity;
  document.getElementById('sfxVolume').value = settings.sfxVolume;
  document.getElementById('graphicsQuality').value = settings.graphicsQuality;
  
  // Configurar toggles
  setToggleState('invertY', settings.invertY);
  setToggleState('soundEnabled', settings.soundEnabled);
  setToggleState('showHologram', settings.showHologram);
  setToggleState('showScanner', settings.showScanner);
  setToggleState('showWaypoints', settings.showWaypoints);
  setToggleState('showPlanetView', settings.showPlanetView);
  
  // Aplicar configurações visuais
  applyVisualSettings();
  
  showFeedback('Configurações redefinidas para o padrão');
}

// Configurar estado do toggle
function setToggleState(toggleId, isActive) {
  const toggle = document.getElementById(toggleId);
  if (isActive) {
    toggle.classList.add('active');
  } else {
    toggle.classList.remove('active');
  }
}

// Aplicar configurações visuais
function applyVisualSettings() {
  // Mostrar/ocultar holograma
  const hologramContainer = document.querySelector('.hologram-container');
  if (hologramContainer) {
    hologramContainer.style.display = settings.showHologram ? 'block' : 'none';
  }
  
  // Mostrar/ocultar scanner
  const scannerContainer = document.querySelector('.scanner-container');
  if (scannerContainer) {
    scannerContainer.style.display = settings.showScanner ? 'block' : 'none';
  }
  
  // Mostrar/ocultar waypoints
  const waypointContainer = document.querySelector('.waypoint-container');
  if (waypointContainer) {
    waypointContainer.style.display = settings.showWaypoints ? 'block' : 'none';
  }
  
  // Mostrar/ocultar visão planetária
  const planetView = document.querySelector('.planet-view');
  if (planetView) {
    planetView.style.display = settings.showPlanetView ? 'block' : 'none';
  }
}

// Configurar eventos dos controles de configurações
function setupSettingsEvents() {
  // Sensibilidade do mouse
  document.getElementById('mouseSensitivity').addEventListener('input', (e) => {
    settings.mouseSensitivity = parseFloat(e.target.value);
  });
  
  // Volume dos efeitos
  document.getElementById('sfxVolume').addEventListener('input', (e) => {
    settings.sfxVolume = parseFloat(e.target.value);
  });
  
  // Qualidade gráfica
  document.getElementById('graphicsQuality').addEventListener('change', (e) => {
    settings.graphicsQuality = e.target.value;
    applyVisualSettings();
  });
  
  // Toggles
  const toggles = ['invertY', 'soundEnabled', 'showHologram', 'showScanner', 'showWaypoints', 'showPlanetView'];
  toggles.forEach(toggleId => {
    const toggle = document.getElementById(toggleId);
    toggle.addEventListener('click', () => {
      const isActive = toggle.classList.contains('active');
      toggle.classList.toggle('active');
      settings[toggleId] = !isActive;
      
      if (toggleId === 'showHologram' || toggleId === 'showScanner' || 
          toggleId === 'showWaypoints' || toggleId === 'showPlanetView') {
        applyVisualSettings();
      }
    });
  });
  
  // Botões
  document.getElementById('resetSettings').addEventListener('click', resetSettings);
  document.getElementById('saveSettings').addEventListener('click', saveSettings);
  
  // Botão de fechar com ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const settingsPanel = document.getElementById('settingsPanel');
      if (settingsPanel.classList.contains('active')) {
        settingsPanel.classList.remove('active');
      }
    }
  });
}

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
  boostEnergy: 100,
  boostRechargeRate: 0.2,
  boostConsumptionRate: 0.8,
  turboMultiplier: 3.0
};

// UI/Canvas
const view = document.getElementById('view');
const ctx = view.getContext('2d');
const mapCanvas = document.getElementById('map');
const mapCtx = mapCanvas.getContext('2d');
const hologramCanvas = document.getElementById('hologram');
const hologramCtx = hologramCanvas.getContext('2d');
const hudVel = document.getElementById('vel');
const hudHdg = document.getElementById('hdg');
const hudPos = document.getElementById('pos');
const hudTgt = document.getElementById('tgt');
const hudEnergy = document.getElementById('energy');
const toast = document.getElementById('toast');
const minimapEl = document.getElementById('minimap');
const speedEffect = document.getElementById('speedEffect');
const feedback = document.getElementById('feedback');

// Elementos da visão planetária
const planetView = document.getElementById('planetView');
const planetImage = document.getElementById('planetImage');
const planetName = document.getElementById('planetName');
const planetDistance = document.getElementById('planetDistance');
const planetDiameter = document.getElementById('planetDiameter');
const planetTemp = document.getElementById('planetTemp');
const planetAtmosphere = document.getElementById('planetAtmosphere');
const planetProximityBar = document.getElementById('planetProximityBar');

let W = 0, H = 0, stars = [], showMap = true, lastT = 0;

// Waypoints
const waypoints = [
  {name: 'Estação Espacial', x: 20000, y: 20000, type: 'station'},
  {name: 'Cinturão de Asteroides', x: 70000, y: 30000, type: 'asteroids'},
  {name: 'Anel de Saturno', x: 80000, y: 80000, type: 'planet'},
  {name: 'Laboratório de Pesquisa', x: 30000, y: 70000, type: 'lab'},
  {name: 'Polo Norte de Marte', x: 40000, y: 60000, type: 'planet'}
];

let targetWaypoint = null;
let nearestPlanet = null;

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
  hologramCanvas.width = window.innerWidth > 900 ? 196 : 156;
  hologramCanvas.height = window.innerWidth > 900 ? 120 : 90;
}
window.addEventListener('resize', resize);

// Controles
const keys = {};
window.addEventListener('keydown', e=>{
  keys[e.key.toLowerCase()] = true;
  if(["ArrowLeft","ArrowRight","ArrowUp","ArrowDown"].includes(e.key)) e.preventDefault();
  if(e.key.toLowerCase()==='m'){ showMap = !showMap; minimapEl.style.display = showMap?'block':'none'; }
  if(e.key.toLowerCase()==='r'){ ship.x=500; ship.y=500; ship.heading=0; ship.vel=0; flash("POSIÇÃO RESETADA"); }
  if(e.key.toLowerCase()==='escape'){ 
    const settingsPanel = document.getElementById('settingsPanel');
    settingsPanel.classList.toggle('active');
  }
});
window.addEventListener('keyup', e=>{ keys[e.key.toLowerCase()] = false; });

function flash(msg){
  toast.textContent = msg;
  toast.style.display='block';
  clearTimeout(flash._t); flash._t = setTimeout(()=>toast.style.display='none', 1500);
}

function showFeedback(message) {
  feedback.textContent = message;
  feedback.style.opacity = 1;
  setTimeout(() => {
    feedback.style.opacity = 0;
  }, 1500);
}

// Criar painel de navegação por waypoints
function createWaypointNav() {
  const navContainer = document.getElementById('waypointNav');
  if (!navContainer) return;
  
  navContainer.innerHTML = '';
  
  waypoints.forEach((waypoint, index) => {
    const item = document.createElement('div');
    item.className = 'waypoint-item';
    item.dataset.index = index;
    
    const nameDiv = document.createElement('div');
    nameDiv.textContent = waypoint.name;
    
    const distDiv = document.createElement('div');
    distDiv.className = 'waypoint-distance';
    distDiv.textContent = '0 u';
    
    item.appendChild(nameDiv);
    item.appendChild(distDiv);
    
    // Adicionar evento de clique
    item.addEventListener('click', () => {
      selectWaypoint(index);
    });
    
    navContainer.appendChild(item);
  });
}

// Selecionar waypoint
function selectWaypoint(index) {
  targetWaypoint = waypoints[index];
  
  // Atualizar UI
  const items = document.querySelectorAll('.waypoint-item');
  items.forEach((item, i) => {
    if (i === index) {
      item.classList.add('active');
    } else {
      item.classList.remove('active');
    }
  });
  
  // Mostrar feedback
  showFeedback(`Waypoint selecionado: ${targetWaypoint.name}`);
}

// Atualizar painel de waypoints
function updateWaypointNav() {
  const navItems = document.querySelectorAll('.waypoint-item');
  
  waypoints.forEach((waypoint, index) => {
    if (navItems[index]) {
      const dx = waypoint.x - ship.x;
      const dy = waypoint.y - ship.y;
      const dist = Math.sqrt(dx*dx + dy*dy);
      
      const distDiv = navItems[index].querySelector('.waypoint-distance');
      if (distDiv) distDiv.textContent = dist.toFixed(0) + ' u';
      
      // Destacar waypoint selecionado
      if (targetWaypoint === waypoint) {
        navItems[index].classList.add('active');
      } else {
        navItems[index].classList.remove('active');
      }
    }
  });
}

// Atualizar objetos do scanner
function updateScannerObjects() {
  const scanner = document.getElementById('scanner');
  if (!scanner) return;
  
  // Remover objetos antigos
  const oldObjects = scanner.querySelectorAll('.scanner-object');
  oldObjects.forEach(obj => obj.remove());
  
  // Adicionar planetas próximos
  const maxObjects = 6;
  let objectsAdded = 0;
  
  for (const body of bodies) {
    if (objectsAdded >= maxObjects) break;
    
    const dx = body.x - ship.x;
    const dy = body.y - ship.y;
    const distance = Math.hypot(dx, dy);
    
    if (distance < 30000) {
      const angle = Math.atan2(dy, dx);
      
      const objElement = document.createElement('div');
      objElement.className = 'scanner-object';
      
      // Calcular posição no scanner (baseado no ângulo)
      const radius = 35; // Raio do scanner
      const centerX = 50; // Centro do scanner
      const centerY = 50; // Centro do scanner
      
      // Converter ângulo para coordenadas
      const x = centerX + radius * Math.cos(angle);
      const y = centerY + radius * Math.sin(angle);
      
      // Posicionar o objeto
      objElement.style.left = `${x}px`;
      objElement.style.top = `${y}px`;
      objElement.style.transform = 'translate(-50%, -50%)';
      
      // Cor baseada no tipo
      if (body.name === "Sol") {
        objElement.style.background = 'rgba(255,200,0,0.9)';
      } else {
        objElement.style.background = 'rgba(0,255,255,0.9)';
      }
      
      // Tamanho baseado na distância (quanto mais perto, maior)
      const size = Math.max(4, 10 - (distance / 30000) * 6);
      objElement.style.width = `${size}px`;
      objElement.style.height = `${size}px`;
      
      // Adicionar tooltip
      objElement.title = `${body.name} - ${distance.toFixed(0)}u`;
      
      scanner.appendChild(objElement);
      objectsAdded++;
    }
  }
  
  // Adicionar waypoints próximos
  for (const waypoint of waypoints) {
    if (objectsAdded >= maxObjects) break;
    
    const dx = waypoint.x - ship.x;
    const dy = waypoint.y - ship.y;
    const distance = Math.sqrt(dx*dx + dy*dy);
    
    if (distance < 30000) {
      const angle = Math.atan2(dy, dx);
      
      const objElement = document.createElement('div');
      objElement.className = 'scanner-object';
      
      // Calcular posição no scanner (baseado no ângulo)
      const radius = 35; // Raio do scanner
      const centerX = 50; // Centro do scanner
      const centerY = 50; // Centro do scanner
      
      // Converter ângulo para coordenadas
      const x = centerX + radius * Math.cos(angle);
      const y = centerY + radius * Math.sin(angle);
      
      // Posicionar o objeto
      objElement.style.left = `${x}px`;
      objElement.style.top = `${y}px`;
      objElement.style.transform = 'translate(-50%, -50%)';
      
      // Cor para waypoints
      objElement.style.background = 'rgba(247,37,133,0.9)';
      
      // Tamanho baseado na distância (quanto mais perto, maior)
      const size = Math.max(4, 8 - (distance / 30000) * 4);
      objElement.style.width = `${size}px`;
      objElement.style.height = `${size}px`;
      
      // Adicionar tooltip
      objElement.title = `${waypoint.name} - ${distance.toFixed(0)}u`;
      
      scanner.appendChild(objElement);
      objectsAdded++;
    }
  }
}

// Atualizar visão planetária
function updatePlanetView() {
  if (!settings.showPlanetView) return;
  
  // Encontrar o planeta mais próximo
  let closestPlanet = null;
  let closestDistance = Infinity;
  
  for (const body of bodies) {
    const dx = body.x - ship.x;
    const dy = body.y - ship.y;
    const distance = Math.hypot(dx, dy);
    
    if (distance < closestDistance) {
      closestDistance = distance;
      closestPlanet = body;
    }
  }
  
  nearestPlanet = closestPlanet;
  
  if (nearestPlanet) {
    // Atualizar informações do planeta
    planetName.textContent = nearestPlanet.name;
    planetDistance.textContent = closestDistance.toFixed(0) + " u";
    planetDiameter.textContent = (nearestPlanet.r * 2).toFixed(0) + " km";
    planetTemp.textContent = nearestPlanet.temp || "Desconhecida";
    planetAtmosphere.textContent = nearestPlanet.atmosphere || "Desconhecida";
    
    // Atualizar imagem do planeta
    if (cache[nearestPlanet.img]) {
      planetImage.src = IMG_PATH + nearestPlanet.img;
    }
    
    // Atualizar barra de proximidade
    const proximityPercent = Math.min(100, (closestDistance / 10000) * 100);
    planetProximityBar.style.width = proximityPercent + "%";
    
    // Alterar cor da barra baseado na proximidade
    if (proximityPercent < 20) {
      planetProximityBar.style.background = "linear-gradient(90deg, var(--danger), var(--ok))";
    } else if (proximityPercent < 50) {
      planetProximityBar.style.background = "linear-gradient(90deg, #ff9500, var(--ok))";
    } else {
      planetProximityBar.style.background = "linear-gradient(90deg, var(--ok), #00a8ff)";
    }
  }
}

// Desenhar mapa holográfico
function drawHologram() {
  if (!settings.showHologram) return;
  
  const c = hologramCtx;
  const width = hologramCanvas.width;
  const height = hologramCanvas.height;
  
  // Limpar canvas
  c.clearRect(0, 0, width, height);
  
  // Desenhar fundo com gradiente
  const gradient = c.createLinearGradient(0, 0, width, height);
  gradient.addColorStop(0, 'rgba(10, 0, 30, 0.95)');
  gradient.addColorStop(1, 'rgba(30, 0, 60, 0.95)');
  c.fillStyle = gradient;
  c.fillRect(0, 0, width, height);
  
  // Desenhar borda
  c.strokeStyle = 'rgba(157, 78, 221, 0.6)';
  c.lineWidth = 2;
  c.strokeRect(2, 2, width-4, height-4);
  
  // Centro do holograma
  const centerX = width / 2;
  const centerY = height / 2;
  
  // Desenhar grade de fundo
  c.strokeStyle = 'rgba(157, 78, 221, 0.2)';
  c.lineWidth = 1;
  const gridSize = 20;
  
  // Grade horizontal
  for (let y = 20; y < height - 10; y += gridSize) {
    c.beginPath();
    c.moveTo(10, y);
    c.lineTo(width - 10, y);
    c.stroke();
  }
  
  // Grade vertical
  for (let x = 10; x < width - 10; x += gridSize) {
    c.beginPath();
    c.moveTo(x, 20);
    c.lineTo(x, height - 10);
    c.stroke();
  }
  
  // Desenhar eixos
  c.strokeStyle = 'rgba(157, 78, 221, 0.5)';
  c.lineWidth = 2;
  
  // Eixo X
  c.beginPath();
  c.moveTo(centerX - 60, centerY);
  c.lineTo(centerX + 60, centerY);
  c.stroke();
  
  // Eixo Z
  c.beginPath();
  c.moveTo(centerX, centerY - 40);
  c.lineTo(centerX, centerY + 40);
  c.stroke();
  
  // Desenhar corpos celestes
  const scale = 0.003; // Escala para o holograma
  
  for (const body of bodies) {
    const x = centerX + (body.x - WORLD_W/2) * scale;
    const y = centerY + (body.y - WORLD_H/2) * scale;
    
    // Desenhar corpo
    if (body.name === "Sol") {
      c.fillStyle = 'rgba(255, 200, 0, 0.9)';
    } else {
      c.fillStyle = 'rgba(100, 150, 255, 0.9)';
    }
    
    c.beginPath();
    c.arc(x, y, Math.max(3, body.r * scale * 0.5), 0, Math.PI * 2);
    c.fill();
    
    // Desenhar contorno
    c.strokeStyle = 'rgba(255, 255, 255, 0.9)';
    c.lineWidth = 1;
    c.stroke();
  }
  
  // Desenhar waypoints
  for (const waypoint of waypoints) {
    const x = centerX + (waypoint.x - WORLD_W/2) * scale;
    const y = centerY + (waypoint.y - WORLD_H/2) * scale;
    
    // Desenhar waypoint
    c.fillStyle = 'rgba(247, 37, 133, 0.9)';
    c.beginPath();
    c.arc(x, y, 4, 0, Math.PI * 2);
    c.fill();
    
    // Desenhar contorno
    c.strokeStyle = 'rgba(255, 255, 255, 0.9)';
    c.lineWidth = 1;
    c.stroke();
    
    // Destacar se for o waypoint selecionado
    if (targetWaypoint === waypoint) {
      c.strokeStyle = 'rgba(247, 37, 133, 0.9)';
      c.lineWidth = 2;
      c.stroke();
      
      // Desenhar linha conectando à nave
      const shipX = centerX + (ship.x - WORLD_W/2) * scale;
      const shipY = centerY + (ship.y - WORLD_H/2) * scale;
      
      c.beginPath();
      c.moveTo(shipX, shipY);
      c.lineTo(x, y);
      c.stroke();
    }
  }
  
  // Desenhar posição da nave
  const shipX = centerX + (ship.x - WORLD_W/2) * scale;
  const shipY = centerY + (ship.y - WORLD_H/2) * scale;
  
  // Desenhar nave (triângulo)
  c.fillStyle = '#ff0';
  c.beginPath();
  c.moveTo(shipX, shipY - 6);
  c.lineTo(shipX - 4, shipY + 4);
  c.lineTo(shipX + 4, shipY + 4);
  c.closePath();
  c.fill();
  
  // Desenhar contorno da nave
  c.strokeStyle = 'rgba(255, 255, 255, 0.9)';
  c.lineWidth = 1;
  c.stroke();
  
  // Desenhar linha de direção se houver waypoint selecionado
  if (targetWaypoint) {
    const waypointX = centerX + (targetWaypoint.x - WORLD_W/2) * scale;
    const waypointY = centerY + (targetWaypoint.y - WORLD_H/2) * scale;
    
    c.strokeStyle = 'rgba(247, 37, 133, 0.7)';
    c.lineWidth = 2;
    c.setLineDash([8, 4]);
    c.beginPath();
    c.moveTo(shipX, shipY);
    c.lineTo(waypointX, waypointY);
    c.stroke();
    c.setLineDash([]);
  }
}

// Atualizar efeito de velocidade
function updateSpeedEffect() {
  if (speedEffect) {
    const opacity = Math.min(0.7, Math.abs(ship.vel) / ship.maxSpeed * 0.7);
    speedEffect.style.opacity = opacity;
  }
}

// Botões de controle
document.getElementById('brakeBtn').onclick = ()=>{
  ship.vel *= 0.5;
  showFeedback("FREIO APLICADO");
};

document.getElementById('boostBtn').onclick = ()=>{
  if (ship.boostEnergy > 20) {
    const rad = ship.heading * Math.PI / 180;
    ship.vel += Math.cos(rad) * ship.turboMultiplier * 100;
    ship.boostEnergy -= 30;
    showFeedback("TURBO ATIVADO");
  } else {
    showFeedback("TURBO SEM ENERGIA");
  }
};

/* ======= Simulação + Render ======= */
function clamp(v,a,b){ return Math.max(a, Math.min(b, v)); }
function deg2rad(d){ return d*Math.PI/180; }

function update(dt){
  // Rotação por setas
  if(keys['arrowleft'])  ship.heading -= ship.turnRate*dt;
  if(keys['arrowright']) ship.heading += ship.turnRate*dt;
  // Inclinação "visual"
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
  
  // Gerenciar energia do turbo
  if (keys['shift'] && ship.boostEnergy > 0) {
    ship.boostEnergy -= ship.boostConsumptionRate;
  } else {
    ship.boostEnergy = Math.min(100, ship.boostEnergy + ship.boostRechargeRate);
  }
  
  // Atualizar HUD de energia
  if (hudEnergy) {
    hudEnergy.textContent = Math.round(ship.boostEnergy) + '%';
  }
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
    // "Altura" visual (pitch só desloca verticalmente para sensação)
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
  hudTgt.textContent = targetWaypoint ? `${targetWaypoint.name} (${nearestDist.toFixed(0)}px)` : `${nearestName} (${nearestDist.toFixed(0)}px)`;

  // "Retícula" simples
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
  // waypoints
  for(const waypoint of waypoints){
    const x = waypoint.x * sx;
    const y = waypoint.y * sy;
    mapCtx.fillStyle = targetWaypoint === waypoint ? '#ff5a5a' : '#f72585';
    mapCtx.beginPath(); mapCtx.arc(x, y, 3, 0, Math.PI*2); mapCtx.fill();
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
  
  // Linha para waypoint se houver um selecionado
  if (targetWaypoint) {
    const wx = targetWaypoint.x * sx;
    const wy = targetWaypoint.y * sy;
    mapCtx.strokeStyle = '#f72585';
    mapCtx.setLineDash([5, 3]);
    mapCtx.beginPath();
    mapCtx.moveTo(nx, ny);
    mapCtx.lineTo(wx, wy);
    mapCtx.stroke();
    mapCtx.setLineDash([]);
  }
}

async function boot(){
  resize();
  makeStars();
  randomizeBodies(); // <<< distribuição aleatória no setor 100k x 100k
  
  // Carregar configurações
  loadSettings();
  
  // Configurar eventos de configurações
  setupSettingsEvents();

  // carrega imagens
  await Promise.all(bodies.map(async b=>{
    const im = await loadImage(b.img);
    if(im) cache[b.img]=im;
  }));
  
  // Criar waypoints
  createWaypointNav();
  
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
  drawHologram();
  updateWaypointNav();
  updateScannerObjects();
  updatePlanetView();
  updateSpeedEffect();
  requestAnimationFrame(loop);
}

boot();
</script>
</body>
</html>