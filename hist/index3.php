<?php
// index3.php — Jogo de Nave Espacial com Navegação em Primeira Pessoa
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1" />
<title>Nave Espacial Interestelar - Navegação em Primeira Pessoa</title>
<style>
  :root { --hud: #0ff; --bg:#000; --accent:#1e90ff; --danger:#ff3b3b; --hologram:#9d4edd; --waypoint:#f72585; }
  * { box-sizing: border-box; }
  html, body { margin:0; height:100%; background:#000; color:#eaeaea; font-family:system-ui, Arial, sans-serif; overflow:hidden; }
  .wrap { position:relative; width:100%; height:100dvh; overflow:hidden; background:#000; touch-action:none; }
  canvas.layer { position:absolute; inset:0; width:100%; height:100%; display:block; }
  #universe { z-index:10; }
  #planets  { z-index:20; }
  #others   { z-index:30; }
  #pvp      { z-index:25; }
  #shots    { z-index:40; }
  #hologram { z-index:15; }

  /* Controles UI */
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
  
  /* Mira no centro da tela */
  .crosshair {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid rgba(0, 255, 255, 0.7);
    border-radius: 50%;
    pointer-events: none;
    z-index: 45;
  }
  .crosshair::before, .crosshair::after {
    content: '';
    position: absolute;
    background: rgba(0, 255, 255, 0.7);
  }
  .crosshair::before {
    width: 2px;
    height: 10px;
    top: 5px;
    left: 9px;
  }
  .crosshair::after {
    width: 10px;
    height: 2px;
    top: 9px;
    left: 5px;
  }
  
  /* Indicador de velocidade */
  .speed-indicator {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0,0,0,0.7);
    border: 1px solid rgba(0,255,255,0.3);
    border-radius: 10px;
    padding: 10px;
    width: 180px;
    z-index: 50;
  }
  .speed-title {
    font-weight: bold;
    margin-bottom: 5px;
    color: #0ff;
  }
  .speed-bar-container {
    width: 100%;
    height: 10px;
    background: rgba(255,255,255,0.1);
    border-radius: 5px;
    overflow: hidden;
  }
  .speed-bar {
    height: 100%;
    background: linear-gradient(90deg, #0ff, #1e90ff);
    width: 50%;
    transition: width 0.2s;
  }
  
  /* Mapa Holográfico 3D */
  .hologram-container {
    position: absolute;
    bottom: 140px;
    right: 12px;
    background: rgba(0,0,0,0.8);
    border: 3px solid rgba(157,78,221,0.8);
    border-radius: 12px;
    padding: 12px;
    width: 350px;
    z-index: 60;
    box-shadow: 0 0 30px rgba(157,78,221,0.5);
    perspective: 1000px;
  }
  .hologram-title {
    font-weight: bold;
    margin-bottom: 10px;
    color: var(--hologram);
    font-size: 16px;
    text-align: center;
    text-shadow: 0 0 10px rgba(157,78,221,0.7);
  }
  #hologram {
    border-radius: 8px;
    background: rgba(10,0,30,0.9);
    border: 1px solid rgba(157,78,221,0.5);
    width: 326px;
    height: 220px;
  }
  
  /* Scanner de Proximidade */
  .scanner-container {
    position: absolute;
    top: 120px;
    left: 12px;
    background: rgba(0,0,0,0.7);
    border: 2px solid rgba(247,37,133,0.8);
    border-radius: 50%;
    width: 120px;
    height: 120px;
    z-index: 55;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 15px rgba(247,37,133,0.3);
  }
  .scanner-title {
    position: absolute;
    top: -25px;
    left: 0;
    right: 0;
    text-align: center;
    font-weight: bold;
    color: var(--waypoint);
    font-size: 12px;
  }
  .scanner {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(30,30,30,0.8), rgba(10,10,10,0.9));
    border: 1px solid rgba(247,37,133,0.5);
    overflow: hidden;
  }
  .scanner-sweep {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 2px;
    height: 45px;
    background: linear-gradient(to top, transparent, rgba(247,37,133,0.9));
    transform-origin: bottom center;
    transform: translate(-50%, -100%) rotate(0deg);
    border-radius: 1px;
    box-shadow: 0 0 10px rgba(247,37,133,0.7);
    animation: scan 4s linear infinite;
  }
  @keyframes scan {
    0% { transform: translate(-50%, -100%) rotate(0deg); }
    100% { transform: translate(-50%, -100%) rotate(360deg); }
  }
  .scanner-center {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 12px;
    height: 12px;
    background: var(--waypoint);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 8px rgba(247,37,133,0.8);
  }
  .scanner-object {
    position: absolute;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(0,255,255,0.9);
    box-shadow: 0 0 5px rgba(0,255,255,0.7);
  }
  
  /* Navegação por Waypoints */
  .waypoint-container {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(0,0,0,0.7);
    border: 1px solid rgba(247,37,133,0.3);
    border-radius: 10px;
    padding: 10px;
    width: 200px;
    z-index: 50;
  }
  .waypoint-title {
    font-weight: bold;
    margin-bottom: 8px;
    color: var(--waypoint);
  }
  .waypoint-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    cursor: pointer;
    transition: background 0.2s;
  }
  .waypoint-item:hover {
    background: rgba(247,37,133,0.2);
    border-radius: 5px;
    padding-left: 5px;
  }
  .waypoint-item.active {
    background: rgba(247,37,133,0.3);
    border-radius: 5px;
    padding-left: 5px;
    font-weight: bold;
  }
  .waypoint-distance {
    color: #aaa;
    font-size: 11px;
  }
  
  /* Controles de Navegação Simplificados */
  .controls-container {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 15px;
    z-index: 60;
  }
  .control-btn {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(30,144,255,0.2);
    border: 2px solid rgba(30,144,255,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 0 15px rgba(30,144,255,0.3);
  }
  .control-btn:hover {
    background: rgba(30,144,255,0.4);
    transform: scale(1.05);
  }
  .control-btn:active {
    transform: scale(0.95);
  }
  
  /* Efeito de Velocidade */
  .speed-effect {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 5;
    opacity: 0;
    background: radial-gradient(circle at center, transparent 30%, rgba(0,100,255,0.1) 70%);
    transition: opacity 0.3s;
  }
  
  /* Feedback Visual */
  .feedback {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 18px;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 0 10px rgba(0,255,255,0.8);
    pointer-events: none;
    z-index: 70;
    opacity: 0;
    transition: opacity 0.3s;
  }
  
  /* Constelações */
  .constellation-container {
    position: absolute;
    bottom: 380px;
    right: 12px;
    background: rgba(0,0,0,0.7);
    border: 1px solid rgba(157,78,221,0.3);
    border-radius: 10px;
    padding: 10px;
    width: 200px;
    z-index: 50;
  }
  .constellation-title {
    font-weight: bold;
    margin-bottom: 8px;
    color: var(--hologram);
    text-align: center;
  }
  .constellation-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
  }
  .constellation-item {
    width: 50px;
    height: 50px;
    background: rgba(157,78,221,0.1);
    border: 1px solid rgba(157,78,221,0.3);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 20px;
  }
  .constellation-item:hover {
    background: rgba(157,78,221,0.3);
    transform: scale(1.05);
  }
  .constellation-item.active {
    background: rgba(157,78,221,0.5);
    border-color: var(--hologram);
    box-shadow: 0 0 10px rgba(157,78,221,0.7);
  }
  
  /* Painel de Configurações */
  .settings-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.9);
    border: 2px solid rgba(157,78,221,0.8);
    border-radius: 16px;
    padding: 20px;
    width: 400px;
    max-height: 80vh;
    overflow-y: auto;
    z-index: 100;
    display: none;
    box-shadow: 0 0 30px rgba(157,78,221,0.5);
  }
  .settings-container.active {
    display: block;
  }
  .settings-title {
    font-size: 20px;
    font-weight: bold;
    color: var(--hologram);
    margin-bottom: 20px;
    text-align: center;
    text-shadow: 0 0 10px rgba(157,78,221,0.7);
  }
  .settings-group {
    margin-bottom: 20px;
  }
  .settings-group-title {
    font-size: 16px;
    font-weight: bold;
    color: #fff;
    margin-bottom: 10px;
    border-bottom: 1px solid rgba(157,78,221,0.3);
    padding-bottom: 5px;
  }
  .setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding: 8px;
    background: rgba(30,30,30,0.5);
    border-radius: 8px;
  }
  .setting-label {
    font-size: 14px;
    color: #eaeaea;
  }
  .setting-control {
    display: flex;
    align-items: center;
  }
  .slider {
    width: 120px;
    height: 6px;
    background: rgba(157,78,221,0.3);
    border-radius: 3px;
    outline: none;
    -webkit-appearance: none;
  }
  .slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    background: var(--hologram);
    border-radius: 50%;
    cursor: pointer;
  }
  .slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: var(--hologram);
    border-radius: 50%;
    cursor: pointer;
    border: none;
  }
  .toggle {
    position: relative;
    width: 50px;
    height: 24px;
    background: rgba(157,78,221,0.3);
    border-radius: 12px;
    cursor: pointer;
  }
  .toggle.active {
    background: var(--hologram);
  }
  .toggle-slider {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
  }
  .toggle.active .toggle-slider {
    transform: translateX(26px);
  }
  .select {
    background: rgba(30,30,30,0.7);
    border: 1px solid rgba(157,78,221,0.5);
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
  }
  .settings-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
  }
  .settings-btn {
    padding: 10px 20px;
    background: rgba(30,144,255,0.2);
    border: 1px solid rgba(30,144,255,0.5);
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s;
  }
  .settings-btn:hover {
    background: rgba(30,144,255,0.4);
  }
  .settings-btn.primary {
    background: rgba(157,78,221,0.3);
    border-color: var(--hologram);
  }
  .settings-btn.primary:hover {
    background: rgba(157,78,221,0.5);
  }
  
  @media (max-width: 900px) {
    .hud .panel:first-child { display:none; }
    .waypoint-container {
      width: 150px;
      font-size: 12px;
    }
    .speed-indicator {
      width: 150px;
    }
    .hologram-container {
      width: 280px;
    }
    #hologram {
      width: 256px;
      height: 170px;
    }
    .scanner-container {
      width: 100px;
      height: 100px;
    }
    .scanner {
      width: 80px;
      height: 80px;
    }
    .scanner-sweep {
      height: 35px;
      width: 2px;
    }
    .controls-container {
      bottom: 20px;
    }
    .control-btn {
      width: 60px;
      height: 60px;
      font-size: 24px;
    }
    .constellation-container {
      bottom: 320px;
      width: 150px;
    }
    .constellation-item {
      width: 40px;
      height: 40px;
      font-size: 16px;
    }
    .settings-container {
      width: 90%;
      max-width: 350px;
    }
  }
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
  <canvas id="hologram" class="layer"></canvas>
  
  <!-- Efeito de Velocidade -->
  <div class="speed-effect" id="speedEffect"></div>
  
  <!-- Mira no centro da tela -->
  <div class="crosshair"></div>
  
  <!-- Feedback Visual -->
  <div class="feedback" id="feedback"></div>
  
  <!-- Indicador de Velocidade -->
  <div class="speed-indicator">
    <div class="speed-title">Velocidade da Nave</div>
    <div class="speed-bar-container">
      <div class="speed-bar" id="speedBar"></div>
    </div>
    <div style="margin-top: 5px; font-size: 11px;">
      <span>W/↑: Acelerar</span> | <span>S/↓: Frear</span>
    </div>
  </div>
  
  <!-- Scanner de Proximidade -->
  <div class="scanner-container">
    <div class="scanner-title">SCANNER</div>
    <div class="scanner" id="scanner">
      <div class="scanner-sweep"></div>
      <div class="scanner-center"></div>
    </div>
  </div>
  
  <!-- Navegação por Waypoints -->
  <div class="waypoint-container">
    <div class="waypoint-title">WAYPOINTS</div>
    <div id="waypointNav"></div>
  </div>
  
  <!-- Mapa Holográfico 3D -->
  <div class="hologram-container">
    <div class="hologram-title">🌌 MAPA HOLOGRÁFICO 3D 🌌</div>
    <canvas id="hologram"></canvas>
  </div>
  
  <!-- Constelações -->
  <div class="constellation-container">
    <div class="constellation-title">CONSTELAÇÕES</div>
    <div class="constellation-grid">
      <div class="constellation-item" data-constellation="orion">♈</div>
      <div class="constellation-item" data-constellation="ursa">♉</div>
      <div class="constellation-item" data-constellation="lyra">♊</div>
      <div class="constellation-item" data-constellation="cygnus">♋</div>
      <div class="constellation-item" data-constellation="scorpius">♌</div>
      <div class="constellation-item" data-constellation="aquarius">♍</div>
    </div>
  </div>
  
  <!-- Controles de Navegação Simplificados -->
  <div class="controls-container">
    <div class="control-btn" id="brakeBtn" title="Frear (S/↓)">⏹️</div>
    <div class="control-btn" id="boostBtn" title="Turbo (Shift)">⚡</div>
  </div>

  <!-- UI / HUD -->
  <div class="ui">
    <div class="legend">
      <div><b>NAVEGAÇÃO EM PRIMEIRA PESSOA</b></div>
      <div>Mouse/Setas: Olhar ao redor</div>
      <div>W/↑: Mover para frente</div>
      <div>S/↓: Frear</div>
      <div>Q/E: Subir/Descer</div>
      <div>Shift: Turbo &nbsp; Espaço: Atirar</div>
      <div>+/-: Zoom &nbsp; B: Parar Total</div>
      <div>ESC: Configurações</div>
      <div>Clique no waypoint para navegar</div>
    </div>
    <div class="notice">Modo Viagem Espacial - Primeira Pessoa!</div>

    <div class="hud">
      <div class="panel">
        <div class="row" style="gap:12px">
          <div class="gauge" id="gauge">
            <div class="needle" id="needle"></div>
            <div class="center"></div>
          </div>
          <div>
            <div class="stat">Velocidade: <b id="spd">0</b></div>
            <div class="stat">Direção: <b id="dir">0°</b></div>
            <div class="stat">Zoom: <b id="zoom">1.0x</b></div>
            <div class="stat">Coordenadas: <b id="coords">0, 0, 0</b></div>
            <div class="stat">Waypoint: <b id="pname">—</b></div>
            <div class="stat">Distância: <b id="pdist">—</b></div>
            <div class="stat">FPS: <b id="fps">—</b></div>
          </div>
        </div>
      </div>

      <div class="row" style="gap:10px">
        <button class="btn" id="btnCenter">Recentrar Câmera</button>
        <button class="btn" id="btnSpawn">Spawn Asteroide</button>
        <button class="btn" id="btnClear">Limpar Tiros</button>
        <button class="btn" id="btnWarp">Warp Rápido</button>
        <button class="btn" id="btnSettings">Configurações</button>
      </div>
    </div>
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
    </div>
    
    <div class="settings-buttons">
      <button class="settings-btn" id="resetSettings">Redefinir</button>
      <button class="settings-btn primary" id="saveSettings">Salvar</button>
    </div>
  </div>
</div>

<script>
// ---------- Sistema de Configurações ----------
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
  showWaypoints: true
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
  
  // Aplicar qualidade gráfica
  const qualitySettings = {
    low: { particleCount: 0.5, effectOpacity: 0.7 },
    medium: { particleCount: 1, effectOpacity: 0.85 },
    high: { particleCount: 1.5, effectOpacity: 1 }
  };
  
  const quality = qualitySettings[settings.graphicsQuality] || qualitySettings.medium;
  // Aqui você pode aplicar as configurações de qualidade aos efeitos visuais
}

// Configurar eventos dos controles de configurações
function setupSettingsEvents() {
  // Sensibilidade do mouse
  document.getElementById('mouseSensitivity').addEventListener('input', (e) => {
    settings.mouseSensitivity = parseFloat(e.target.value);
    state.cameraRotationSpeed = settings.mouseSensitivity;
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
  const toggles = ['invertY', 'soundEnabled', 'showHologram', 'showScanner', 'showWaypoints'];
  toggles.forEach(toggleId => {
    const toggle = document.getElementById(toggleId);
    toggle.addEventListener('click', () => {
      const isActive = toggle.classList.contains('active');
      toggle.classList.toggle('active');
      settings[toggleId] = !isActive;
      
      if (toggleId === 'showHologram' || toggleId === 'showScanner' || toggleId === 'showWaypoints') {
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

// ---------- Dimensão ----------
const wrap = document.getElementById('wrap');
const canvases = {
  universe: document.getElementById('universe'),
  planets : document.getElementById('planets'),
  pvp     : document.getElementById('pvp'),
  others  : document.getElementById('others'),
  shots   : document.getElementById('shots'),
  hologram: document.getElementById('hologram'),
};
const ctx = {};

// Função de redimensionamento
function resize() {
  for (const k in canvases) {
    const c = canvases[k];
    c.width = wrap.clientWidth;
    c.height = wrap.clientHeight;
    ctx[k] = c.getContext('2d');
  }
  // Ajustar tamanho do holograma
  const hologramCanvas = document.getElementById('hologram');
  if (hologramCanvas) {
    const containerWidth = window.innerWidth > 900 ? 326 : 256;
    const containerHeight = window.innerWidth > 900 ? 220 : 170;
    hologramCanvas.width = containerWidth;
    hologramCanvas.height = containerHeight;
    ctx.hologram = hologramCanvas.getContext('2d');
  }
}

window.addEventListener('resize', resize);
resize();

// ---------- Assets (imagens) ----------
const IMG = {
  // Universo
  nebula: 'images/universo.jpg',
  stars : 'images/ceres.jpg',
  // Sol
  sol: 'images/sol.png',
  // Planetas
  terra: 'images/terra.png',
  marte: 'images/marte.png',
  jupter: 'images/jupter.png',
  sartuno: 'images/sartuno.png',
  netuno: 'images/netuno.png',
  venus: 'images/venus.png',
  // Nave/asteroide
  ship: 'images/ceres.png',
  asteroid: 'images/asteroide.png',
};

// ---------- Áudio ----------
const LASER_URLS = [
  'media/laser1.mp3',
  'media/laser2.mp3'
];
const EXPLOSION_URLS = [
  'media/explosion1.mp3',
  'media/explosion2.mp3'
];
const LASER_FALLBACK = 'data:audio/mp3;base64,SUQzAwAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4LjI5LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAADSAAAAETEFNRTMuMTAwVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVQ==';
const EXPLOSION_FALLBACK = 'data:audio/mp3;base64,SUQzAwAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4LjI5LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAADSAAAAETEFNRTMuMTAwVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVQ==';

let laserAudio = new Audio();
let explosionAudio = new Audio();
laserAudio.preload = 'auto';
explosionAudio.preload = 'auto';

// ---------- Estado do "mundo 3D" ----------
const state = {
  t: 0,
  pos: { x:0, y:0, z:0 },
  vel: { x:0, y:0, z:0 },
  yaw: 0, pitch: 0,
  speed: 0,
  baseSpeed: 5.0,
  maxSpeed: 50.0,
  zoom: 1.0,
  bullets: [],
  entities: [],
  planets: [],
  mouse: { x:0, y:0 },
  fps: 0,
  maxZoom: 10.0,
  minZoom: 0.1,
  warpMode: false,
  universeSize: 120000, // 120.000 pixels
  nearestPlanet: null,
  nearestDist: Infinity,
  keysPressed: {},
  sun: { x:0, y:0, z:0, r: 500, name: 'Sol' }, // Sol no centro
  
  // Sistema de navegação em primeira pessoa
  acceleration: 0.3,
  deceleration: 0.15,
  turboMultiplier: 3.0,
  cameraRotationSpeed: settings.mouseSensitivity, // Usar configuração
  maxCameraRotationSpeed: 0.2,
  inertia: 0.92,
  maxSpeed: 100.0,
  currentMaxSpeed: 30.0,
  boostEnergy: 100,
  boostRechargeRate: 0.2,
  boostConsumptionRate: 0.8,
  
  // Novo sistema de navegação
  hologramRotation: { x: 0, y: 0 },
  targetWaypoint: null,
  waypoints: [],
  constellations: {
    orion: { name: "Órion", planets: ["Sol", "Vênus", "Terra"] },
    ursa: { name: "Ursa Maior", planets: ["Marte", "Júpiter", "Saturno"] },
    lyra: { name: "Lira", planets: ["Netuno", "Vênus", "Terra"] },
    cygnus: { name: "Cisne", planets: ["Júpiter", "Saturno", "Netuno"] },
    scorpius: { name: "Escorpião", planets: ["Sol", "Marte", "Júpiter"] },
    aquarius: { name: "Aquário", planets: ["Saturno", "Netuno", "Vênus"] }
  },
  activeConstellation: null,
  scannerObjects: []
};

// Funções utilitárias
function rnd(a,b){ return a + Math.random()*(b-a); }
function clamp(v, a, b){ return Math.max(a, Math.min(b, v)); }

// Carrega imagens
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

// ---------- Mundo inicial ----------
function init(){
  console.log("Inicializando jogo com navegação em primeira pessoa...");
  
  // Carregar configurações
  loadSettings();
  
  // Configurar eventos de configurações
  setupSettingsEvents();
  
  // Planetas em órbita circular ao redor do Sol
  state.planets = [
    { name:'Vênus', img:'venus', x:8000, y:0, z:0, r: 110, color: 'rgba(255,200,100,0.9)', orbitRadius: 8000, angle: 0 },
    { name:'Terra', img:'terra', x:0, y:0, z:16000, r: 120, color: 'rgba(100,200,255,0.9)', orbitRadius: 16000, angle: Math.PI/2 },
    { name:'Marte', img:'marte', x:-32000, y:0, z:0, r: 90, color: 'rgba(255,100,100,0.9)', orbitRadius: 32000, angle: Math.PI },
    { name:'Júpiter', img:'jupter', x:0, y:0, z:-56000, r: 280, color: 'rgba(200,150,100,0.9)', orbitRadius: 56000, angle: 3*Math.PI/2 },
    { name:'Saturno', img:'sartuno', x:76000, y:0, z:0, r: 240, color: 'rgba(220,200,150,0.9)', orbitRadius: 76000, angle: 0 },
    { name:'Netuno', img:'netuno', x:0, y:0, z:96000, r: 220, color: 'rgba(100,150,255,0.9)', orbitRadius: 96000, angle: Math.PI/2 }
  ];
  
  // Posição inicial da nave perto de Vênus
  state.pos = { x:8500, y:0, z:0 };
  
  // Entidades iniciais
  for (let i=0;i<20;i++) spawnEntity();
  
  // Criar waypoints
  createWaypoints();
  
  // Criar painel de navegação
  createWaypointNav();
  
  // Inicializar holograma
  initHologram();
  
  // Inicializar scanner
  initScanner();
  
  // Inicializar constelações
  initConstellations();
  
  // Iniciar áudio
  initAudio();
  
  // Iniciar loop do jogo
  loop();
  
  console.log("Jogo inicializado com sucesso!");
}

// ---------- Inicializar Áudio ----------
async function initAudio() {
  try {
    laserAudio.src = LASER_URLS[0];
    await laserAudio.play().catch(()=>{});
    laserAudio.pause(); laserAudio.currentTime = 0;
  } catch (e) {
    try {
      laserAudio.src = LASER_URLS[1];
      await laserAudio.play().catch(()=>{});
      laserAudio.pause(); laserAudio.currentTime = 0;
    } catch (e2) {
      laserAudio.src = LASER_FALLBACK;
    }
  }
  
  try {
    explosionAudio.src = EXPLOSION_URLS[0];
    await explosionAudio.play().catch(()=>{});
    explosionAudio.pause(); explosionAudio.currentTime = 0;
  } catch (e) {
    try {
      explosionAudio.src = EXPLOSION_URLS[1];
      await explosionAudio.play().catch(()=>{});
      explosionAudio.pause(); explosionAudio.currentTime = 0;
    } catch (e2) {
      explosionAudio.src = EXPLOSION_FALLBACK;
    }
  }
}

// ---------- Criar Waypoints ----------
function createWaypoints() {
  state.waypoints = [
    { name: 'Estação Espacial', x: 5000, y: 2000, z: 5000, type: 'station' },
    { name: 'Cinturão de Asteroides', x: -40000, y: 0, z: 0, type: 'asteroids' },
    { name: 'Anel de Saturno', x: 76000, y: 0, z: 0, type: 'planet' },
    { name: 'Polo Norte de Marte', x: -32000, y: 5000, z: 0, type: 'planet' },
    { name: 'Laboratório de Pesquisa', x: 0, y: -3000, z: 40000, type: 'lab' }
  ];
}

// ---------- Painel de Navegação por Waypoints ----------
function createWaypointNav() {
  const navContainer = document.getElementById('waypointNav');
  if (!navContainer) return;
  
  navContainer.innerHTML = '';
  
  state.waypoints.forEach((waypoint, index) => {
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

// ---------- Selecionar Waypoint ----------
function selectWaypoint(index) {
  state.targetWaypoint = state.waypoints[index];
  
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
  showFeedback(`Waypoint selecionado: ${state.targetWaypoint.name}`);
  
  // Atualizar HUD
  const pnameElement = document.getElementById('pname');
  if (pnameElement) pnameElement.textContent = state.targetWaypoint.name;
}

// ---------- Inicializar Holograma ----------
function initHologram() {
  const hologramCanvas = document.getElementById('hologram');
  if (!hologramCanvas) return;
  
  const containerWidth = window.innerWidth > 900 ? 326 : 256;
  const containerHeight = window.innerWidth > 900 ? 220 : 170;
  
  hologramCanvas.width = containerWidth;
  hologramCanvas.height = containerHeight;
  ctx.hologram = hologramCanvas.getContext('2d');
}

// ---------- Inicializar Scanner ----------
function initScanner() {
  // Adicionar objetos ao scanner
  updateScannerObjects();
}

// ---------- Atualizar Objetos do Scanner ----------
function updateScannerObjects() {
  state.scannerObjects = [];
  
  // Adicionar planetas próximos
  state.planets.forEach(planet => {
    const dx = planet.x - state.pos.x;
    const dy = planet.y - state.pos.y;
    const dz = planet.z - state.pos.z;
    const distance = Math.sqrt(dx*dx + dy*dy + dz*dz);
    
    if (distance < 30000) {
      const angle = Math.atan2(dz, dx);
      state.scannerObjects.push({
        name: planet.name,
        distance: distance,
        angle: angle,
        type: 'planet',
        size: planet.r
      });
    }
  });
  
  // Adicionar waypoints próximos
  state.waypoints.forEach(waypoint => {
    const dx = waypoint.x - state.pos.x;
    const dy = waypoint.y - state.pos.y;
    const dz = waypoint.z - state.pos.z;
    const distance = Math.sqrt(dx*dx + dy*dy + dz*dz);
    
    if (distance < 30000) {
      const angle = Math.atan2(dz, dx);
      state.scannerObjects.push({
        name: waypoint.name,
        distance: distance,
        angle: angle,
        type: waypoint.type,
        size: 30
      });
    }
  });
  
  // Ordenar por distância
  state.scannerObjects.sort((a, b) => a.distance - b.distance);
  
  // Renderizar objetos no scanner
  renderScannerObjects();
}

// ---------- Renderizar Objetos no Scanner ----------
function renderScannerObjects() {
  const scanner = document.getElementById('scanner');
  if (!scanner) return;
  
  // Remover objetos antigos
  const oldObjects = scanner.querySelectorAll('.scanner-object');
  oldObjects.forEach(obj => obj.remove());
  
  // Adicionar novos objetos (máximo 6)
  const maxObjects = 6;
  const objectsToShow = state.scannerObjects.slice(0, maxObjects);
  
  objectsToShow.forEach((obj, index) => {
    const objElement = document.createElement('div');
    objElement.className = 'scanner-object';
    
    // Calcular posição no scanner (baseado no ângulo)
    const radius = 35; // Raio do scanner
    const centerX = 50; // Centro do scanner
    const centerY = 50; // Centro do scanner
    
    // Converter ângulo para coordenadas
    const x = centerX + radius * Math.cos(obj.angle);
    const y = centerY + radius * Math.sin(obj.angle);
    
    // Posicionar o objeto
    objElement.style.left = `${x}px`;
    objElement.style.top = `${y}px`;
    objElement.style.transform = 'translate(-50%, -50%)';
    
    // Cor baseada no tipo
    if (obj.type === 'planet') {
      objElement.style.background = 'rgba(100,200,255,0.9)';
    } else if (obj.type === 'station' || obj.type === 'lab') {
      objElement.style.background = 'rgba(247,37,133,0.9)';
    } else {
      objElement.style.background = 'rgba(157,78,221,0.9)';
    }
    
    // Tamanho baseado na distância (quanto mais perto, maior)
    const size = Math.max(4, 10 - (obj.distance / 30000) * 6);
    objElement.style.width = `${size}px`;
    objElement.style.height = `${size}px`;
    
    // Adicionar tooltip
    objElement.title = `${obj.name} - ${obj.distance.toFixed(0)}u`;
    
    scanner.appendChild(objElement);
  });
}

// ---------- Inicializar Constelações ----------
function initConstellations() {
  const constellationItems = document.querySelectorAll('.constellation-item');
  
  constellationItems.forEach(item => {
    item.addEventListener('click', () => {
      const constellation = item.dataset.constellation;
      selectConstellation(constellation);
    });
  });
}

// ---------- Selecionar Constelação ----------
function selectConstellation(constellation) {
  state.activeConstellation = constellation;
  
  // Atualizar UI
  const items = document.querySelectorAll('.constellation-item');
  items.forEach(item => {
    if (item.dataset.constellation === constellation) {
      item.classList.add('active');
    } else {
      item.classList.remove('active');
    }
  });
  
  // Mostrar feedback
  const constellationName = state.constellations[constellation].name;
  showFeedback(`Constelação selecionada: ${constellationName}`);
  
  // Destacar planetas da constelação no holograma
  updateHologramHighlight();
}

// ---------- Atualizar Destaque no Holograma ----------
function updateHologramHighlight() {
  // Esta função será chamada durante a renderização do holograma
  // para destacar os planetas da constelação ativa
}

// ---------- Spawns ----------
function spawnEntity(){
  state.entities.push({
    kind: 'asteroid',
    x: rnd(-state.universeSize/2, state.universeSize/2),
    y: rnd(-state.universeSize/4, state.universeSize/4),
    z: rnd(-state.universeSize/2, state.universeSize/2),
    vx: rnd(-0.1,0.1),
    vy: rnd(-0.1,0.1),
    vz: rnd(-0.1,0.1),
    r: rnd(20,80),
    rot: rnd(0,Math.PI*2),
    rotSpd: rnd(-0.01,0.01),
    randomVx: rnd(-0.02, 0.02),
    randomVy: rnd(-0.02, 0.02),
    randomVz: rnd(-0.02, 0.02)
  });
}

// ---------- Entrada: mouse/teclado ----------
// Controle do mouse - direção da câmera
wrap.addEventListener('mousemove', e=>{
  // Atualizar posição do mouse
  state.mouse.x = e.clientX;
  state.mouse.y = e.clientY;
  
  // Calcular direção com base na posição do mouse
  const centerX = wrap.clientWidth / 2;
  const centerY = wrap.clientHeight / 2;
  
  // Calcular diferença do centro
  const dx = e.clientX - centerX;
  const dy = e.clientY - centerY;
  
  // Aplicar inversão do eixo Y se configurado
  const pitchMultiplier = settings.invertY ? -1 : 1;
  
  // Calcular ângulos alvo
  const targetYaw = Math.atan2(dx, 100) * 1.2;
  const targetPitch = Math.atan2(dy, 100) * 0.6 * pitchMultiplier;
  
  // Suavizar a rotação da câmera
  state.yaw += (targetYaw - state.yaw) * state.cameraRotationSpeed;
  state.pitch += (targetPitch - state.pitch) * state.cameraRotationSpeed;
  
  // Limitar o pitch para evitar virar de cabeça para baixo
  state.pitch = clamp(state.pitch, -Math.PI/3, Math.PI/3);
});

// Controle do teclado
window.addEventListener('keydown', e=>{
  state.keysPressed[e.code] = true;
  
  // Aceleração - W ou Setas Cima (mover para frente)
  if (e.code==='KeyW' || e.code==='ArrowUp') {
    const dir = forwardVector();
    state.vel.x += dir.x * state.acceleration;
    state.vel.y += dir.y * state.acceleration;
    state.vel.z += dir.z * state.acceleration;
    
    // Limitar velocidade máxima
    const currentSpeed = Math.hypot(state.vel.x, state.vel.y, state.vel.z);
    if (currentSpeed > state.currentMaxSpeed) {
      const ratio = state.currentMaxSpeed / currentSpeed;
      state.vel.x *= ratio;
      state.vel.y *= ratio;
      state.vel.z *= ratio;
    }
  }
  
  // Frenagem - S ou Setas Baixo
  if (e.code==='KeyS' || e.code==='ArrowDown') {
    state.vel.x *= 0.85;
    state.vel.y *= 0.85;
    state.vel.z *= 0.85;
  }
  
  // Controles verticais
  if (e.code==='KeyQ') state.vel.y -= 0.3;
  if (e.code==='KeyE') state.vel.y += 0.3;
  
  // Controle de zoom
  if (e.code==='Equal' || e.code==='NumpadAdd') state.zoom = Math.min(state.maxZoom, state.zoom + 0.2);
  if (e.code==='Minus' || e.code==='NumpadSubtract') state.zoom = Math.max(state.minZoom, state.zoom - 0.2);
  
  // Atirar
  if (e.code==='Space') shoot();
  
  // Abrir/fechar configurações
  if (e.code==='Escape') {
    const settingsPanel = document.getElementById('settingsPanel');
    settingsPanel.classList.toggle('active');
  }
});

window.addEventListener('keyup', e=>{
  state.keysPressed[e.code] = false;
});

// Vetores
function forwardVector(){
  const cosP = Math.cos(state.pitch), sinP = Math.sin(state.pitch);
  const cosY = Math.cos(state.yaw),   sinY = Math.sin(state.yaw);
  return { x: sinY*cosP, y: sinP, z: -cosY*cosP };
}

// Mostrar feedback visual
function showFeedback(message) {
  const feedback = document.getElementById('feedback');
  if (feedback) {
    feedback.textContent = message;
    feedback.style.opacity = 1;
    setTimeout(() => {
      feedback.style.opacity = 0;
    }, 1500);
  }
}

// Atualizar barra de velocidade
function updateSpeedBar() {
  const speedBar = document.getElementById('speedBar');
  if (speedBar) {
    const speedPercent = Math.min(100, (state.speed / state.maxSpeed) * 100);
    speedBar.style.width = speedPercent + '%';
  }
}

// Atualizar efeito de velocidade
function updateSpeedEffect() {
  const speedEffect = document.getElementById('speedEffect');
  if (speedEffect) {
    const opacity = Math.min(0.7, state.speed / state.maxSpeed * 0.7);
    speedEffect.style.opacity = opacity;
  }
}

// Botões UI
document.getElementById('btnCenter').onclick = ()=>{
  state.yaw=0; state.pitch=0;
  showFeedback("VISÃO RECENTRADA");
};

document.getElementById('btnSpawn').onclick = ()=> {
  spawnEntity();
  showFeedback("ASTEROIDE CRIADO");
};

document.getElementById('btnClear').onclick = ()=> {
  state.bullets.length=0;
  showFeedback("TIROS LIMPOS");
};

document.getElementById('btnWarp').onclick = ()=>{
  state.warpMode = !state.warpMode;
  if (state.warpMode) {
    state.currentMaxSpeed = state.maxSpeed;
    showFeedback("MODO WARP ATIVADO");
  } else {
    state.currentMaxSpeed = 30.0;
    showFeedback("MODO WARP DESATIVADO");
  }
  document.getElementById('btnWarp').textContent = state.warpMode ? 'Warp: ON' : 'Warp Rápido';
  document.getElementById('btnWarp').style.background = state.warpMode ? 'rgba(255,100,0,.3)' : 'rgba(30,144,255,.15)';
};

document.getElementById('btnSettings').onclick = ()=>{
  const settingsPanel = document.getElementById('settingsPanel');
  settingsPanel.classList.toggle('active');
};

// Botões de controle
document.getElementById('brakeBtn').onclick = ()=>{
  state.vel.x *= 0.5;
  state.vel.y *= 0.5;
  state.vel.z *= 0.5;
  showFeedback("FREIO APLICADO");
};

document.getElementById('boostBtn').onclick = ()=>{
  if (state.boostEnergy > 20) {
    const dir = forwardVector();
    state.vel.x += dir.x * state.turboMultiplier;
    state.vel.y += dir.y * state.turboMultiplier;
    state.vel.z += dir.z * state.turboMultiplier;
    state.boostEnergy -= 30;
    showFeedback("TURBO ATIVADO");
  } else {
    showFeedback("TURBO SEM ENERGIA");
  }
};

// ---------- Disparos ----------
function shoot(){
  if (!settings.soundEnabled) return;
  
  try { 
    const a = laserAudio.cloneNode(); 
    a.volume = settings.sfxVolume; 
    a.play(); 
  } catch(e){
    console.log("Erro ao tocar som de laser:", e);
  }
  
  const speed = 22;
  const dir = forwardVector();
  state.bullets.push({
    x: state.pos.x, 
    y: state.pos.y, 
    z: state.pos.z,
    vx: dir.x*speed, 
    vy: dir.y*speed, 
    vz: dir.z*speed,
    life: 120
  });
}

// ---------- Projeção 3D -> 2D ----------
function project(x,y,z){
  const cy = Math.cos(state.yaw), sy = Math.sin(state.yaw);
  const cp = Math.cos(state.pitch), sp = Math.sin(state.pitch);

  let dx = x - state.pos.x;
  let dy = y - state.pos.y;
  let dz = z - state.pos.z;

  let dz1 =  dz*cy - dx*sy;
  let dx1 =  dz*sy + dx*cy;
  let dy1 =  dy*cp - dz1*sp;
  let dz2 =  dy*sp + dz1*cp;

  const fov = 700 * state.zoom;
  if (dz2 > -10) return null;
  const sx = (dx1*fov)/(-dz2) + canvases.universe.width/2;
  const sy2 = (dy1*fov)/(-dz2) + canvases.universe.height/2;
  const scale = fov/(-dz2);
  return { x:sx, y:sy2, s:scale, dz:-dz2 };
}

// ---------- Loop principal ----------
let lastTime = performance.now();
let frameCount=0, fpsTime=0;

function loop(){
  try {
    const now = performance.now(), dt = Math.min(33, now-lastTime)/16.666;
    lastTime = now; 
    state.t += dt;

    // Verificar teclas pressionadas
    const turboActive = state.keysPressed['ShiftLeft'] || state.keysPressed['ShiftRight'];
    const turboMultiplier = turboActive ? state.turboMultiplier : 1;
    
    // Gerenciar energia do turbo
    if (turboActive && state.boostEnergy > 0) {
      state.boostEnergy -= state.boostConsumptionRate;
      state.currentMaxSpeed = state.maxSpeed;
    } else {
      state.boostEnergy = Math.min(100, state.boostEnergy + state.boostRechargeRate);
      state.currentMaxSpeed = state.warpMode ? state.maxSpeed : 30.0;
    }
    
    // Aplicar movimento com inércia
    state.pos.x += state.vel.x * dt * 6 * turboMultiplier;
    state.pos.y += state.vel.y * dt * 6 * turboMultiplier;
    state.pos.z += state.vel.z * dt * 6 * turboMultiplier;

    // Manter dentro do universo
    state.pos.x = clamp(state.pos.x, -state.universeSize/2, state.universeSize/2);
    state.pos.y = clamp(state.pos.y, -state.universeSize/4, state.universeSize/4);
    state.pos.z = clamp(state.pos.z, -state.universeSize/2, state.universeSize/2);

    // Aplicar inércia
    state.vel.x *= state.inertia; 
    state.vel.y *= state.inertia; 
    state.vel.z *= state.inertia;
    
    // Calcular velocidade atual
    state.speed = Math.hypot(state.vel.x, state.vel.y, state.vel.z) * 10;

    // Normaliza o ângulo yaw
    state.yaw = (state.yaw + Math.PI * 2) % (Math.PI * 2);

    // Renderização em camadas
    drawUniverse();
    drawPlanets();
    drawPVP();
    updateEntities(dt);
    drawEntities();
    updateBullets(dt);
    drawBullets();
    checkCollisions();
    updateWaypointNav();
    updateHUD();
    updateSpeedBar();
    updateSpeedEffect();
    updateScannerObjects();
    drawHologram();

    requestAnimationFrame(loop);

    // FPS
    frameCount++; 
    fpsTime += dt*16.666;
    if (fpsTime>=500){ 
      const fpsElement = document.getElementById('fps');
      if (fpsElement) {
        fpsElement.textContent = Math.round(frameCount*1000/(fpsTime)); 
      }
      frameCount=0; 
      fpsTime=0; 
    }
  } catch (error) {
    console.error("Erro no loop principal:", error);
  }
}

// ---------- Fundo Universo ----------
function drawUniverse(){
  if (!ctx.universe) return;
  
  const c = ctx.universe, W = canvases.universe.width, H = canvases.universe.height;
  c.clearRect(0,0,W,H);
  const offX = (state.yaw / (Math.PI * 2)) * W;
  const offY = (state.pitch) * 140;

  if (loaded.nebula) {
    c.drawImage(loaded.nebula, -offX, -H*0.2 - offY*0.5, W*1.4, H*1.4);
    c.drawImage(loaded.nebula, W - offX, -H*0.2 - offY*0.5, W*1.4, H*1.4);
  }
  
  if (loaded.stars) {
    c.globalAlpha=0.6;
    c.drawImage(loaded.stars, -offX, -H*0.1 - offY, W*1.2, H*1.2);
    c.drawImage(loaded.stars, W - offX, -H*0.1 - offY, W*1.2, H*1.2);
    c.globalAlpha=1;
  }
  
  c.strokeStyle = 'rgba(255,255,255,.06)';
  for(let r=120; r<W; r+=120){ 
    c.beginPath(); 
    c.arc(W*0.72, H*0.45, r, 0, Math.PI*2); 
    c.stroke(); 
  }
}

// ---------- Planetas ----------
function drawPlanets(){
  if (!ctx.planets) return;
  
  const c = ctx.planets, W=canvases.planets.width, H=canvases.planets.height;
  c.clearRect(0,0,W,H);
  let nearestName='—', nearestDist=Infinity;
  
  // Desenha o Sol
  const sunPr = project(state.sun.x, state.sun.y, state.sun.z);
  if (sunPr) {
    const sunDist = Math.hypot(
      state.sun.x - state.pos.x,
      state.sun.y - state.pos.y,
      state.sun.z - state.pos.z
    );
    
    if (sunDist < nearestDist) {
      nearestDist = sunDist;
      nearestName = 'Sol';
      state.nearestPlanet = state.sun;
    }
    
    const drawR = state.sun.r * sunPr.s;
    c.save();
    c.globalAlpha = 0.95;
    if (loaded.sol) {
      c.beginPath();
      c.arc(sunPr.x, sunPr.y, drawR, 0, Math.PI*2);
      c.closePath();
      c.save();
      c.clip();
      c.drawImage(loaded.sol, sunPr.x-drawR, sunPr.y-drawR, drawR*2, drawR*2);
      c.restore();
      c.strokeStyle='rgba(255,200,0,.5)';
      c.lineWidth=3;
      c.stroke();
    } else {
      // Fallback para o Sol
      const gradient = c.createRadialGradient(sunPr.x, sunPr.y, 0, sunPr.x, sunPr.y, drawR);
      gradient.addColorStop(0, 'rgba(255,255,0,1)');
      gradient.addColorStop(0.7, 'rgba(255,165,0,0.8)');
      gradient.addColorStop(1, 'rgba(255,69,0,0.5)');
      c.fillStyle = gradient;
      c.beginPath(); 
      c.arc(sunPr.x, sunPr.y, drawR, 0, Math.PI*2); 
      c.fill();
      c.strokeStyle='rgba(255,200,0,.6)'; 
      c.stroke();
    }
    c.restore();
  }
  
  // Desenha os planetas
  state.planets.forEach(p=>{
    // Calcular posição circular
    const x = Math.cos(p.angle) * p.orbitRadius;
    const z = Math.sin(p.angle) * p.orbitRadius;
    
    // Atualizar posição do planeta
    p.x = x;
    p.z = z;
    
    const pr = project(p.x, p.y, p.z);
    if (!pr) return;
    
    // Calcular distância real
    const dx = p.x - state.pos.x;
    const dy = p.y - state.pos.y;
    const dz = p.z - state.pos.z;
    const dist = Math.sqrt(dx*dx + dy*dy + dz*dz);
    
    if (dist < nearestDist) {
      nearestDist = dist;
      nearestName = p.name;
      state.nearestPlanet = p;
    }
    
    // Só desenha planetas relativamente próximos
    if(dist > 30000) return;
    
    const baseR = p.r;
    let drawR = baseR * pr.s;
    
    // Zoom automático quando muito próximo
    if (dist < 2000) {
      const proximityFactor = 1 + (2000 - dist) / 2000 * 4;
      drawR = baseR * pr.s * proximityFactor;
      state.zoom = Math.min(state.maxZoom, 1 + (2000 - dist) / 2000 * 4);
    }
    
    c.save();
    c.globalAlpha = 0.95;
    if (loaded[p.img]){
      c.beginPath();
      c.arc(pr.x, pr.y, drawR, 0, Math.PI*2);
      c.closePath();
      c.save();
      c.clip();
      c.drawImage(loaded[p.img], pr.x-drawR, pr.y-drawR, drawR*2, drawR*2);
      c.restore();
      c.strokeStyle='rgba(0,255,255,.25)';
      c.lineWidth=2;
      c.stroke();
    } else {
      c.fillStyle='rgba(100,150,255,.3)';
      c.beginPath(); 
      c.arc(pr.x,pr.y,drawR,0,Math.PI*2); 
      c.fill();
      c.strokeStyle='rgba(0,200,255,.4)'; 
      c.stroke();
    }
    c.restore();
  });
  
  // Se não está próximo de nenhum planeta, diminuir o zoom gradualmente
  if (nearestDist > 3000 && state.zoom > 1) {
    state.zoom = Math.max(1, state.zoom - 0.01);
  }
  
  state.nearestDist = nearestDist;
  const pnameElement = document.getElementById('pname');
  const pdistElement = document.getElementById('pdist');
  if (pnameElement) pnameElement.textContent = state.targetWaypoint ? state.targetWaypoint.name : nearestName;
  if (pdistElement) pdistElement.textContent = nearestDist===Infinity?'—':nearestDist.toFixed(0)+' u';
}

// ---------- Placeholder "Outro Jogador" ----------
function drawPVP(){
  if (!ctx.pvp) return;
  
  const c = ctx.pvp, W=canvases.pvp.width, H=canvases.pvp.height;
  c.clearRect(0,0,W,H);
  c.save();
  c.globalAlpha=0.35;
  c.fillStyle='rgba(30,144,255,.25)';
  c.fillRect(W-210, 14, 196, 80);
  c.strokeStyle='rgba(30,144,255,.6)'; 
  c.strokeRect(W-210,14,196,80);
  c.fillStyle='#cfeaff';
  c.font='12px monospace';
  c.fillText('Modo Viagem Espacial - Primeira Pessoa', W-200, 34);
  c.fillText('Sistema Solar Circular - 120.000 unidades', W-200, 52);
  c.restore();
}

// ---------- Entidades ----------
function updateEntities(dt){
  for (const e of state.entities){
    e.x += e.vx*dt*10;
    e.y += e.vy*dt*10;
    e.z += e.vz*dt*10;
    
    if (e.kind === 'asteroid') {
      e.x += e.randomVx * dt * 10;
      e.y += e.randomVy * dt * 10;
      e.z += e.randomVz * dt * 10;
      
      const distToPlayer = Math.hypot(e.x - state.pos.x, e.y - state.pos.y, e.z - state.pos.z);
      if (distToPlayer > state.universeSize) {
        e.x = rnd(-state.universeSize/2, state.universeSize/2);
        e.y = rnd(-state.universeSize/4, state.universeSize/4);
        e.z = rnd(-state.universeSize/2, state.universeSize/2);
      }
    }
    
    e.rot += e.rotSpd*dt*10;
    
    if (e.z > 200) {
      e.x = rnd(-state.universeSize/2, state.universeSize/2);
      e.y = rnd(-state.universeSize/4, state.universeSize/4);
      e.z = rnd(-state.universeSize/2, state.universeSize/2);
    }
  }
}

function drawEntities(){
  if (!ctx.others) return;
  
  const c = ctx.others, W=canvases.others.width, H=canvases.others.height;
  c.clearRect(0,0,W,H);
  for (const e of state.entities){
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
      c.beginPath(); 
      c.arc(0,0, R, 0, Math.PI*2); 
      c.fill();
    }
    c.restore();
  }
}

// ---------- Tiros ----------
function updateBullets(dt){
  for (const b of state.bullets){
    b.x += b.vx*dt; 
    b.y += b.vy*dt; 
    b.z += b.vz*dt; 
    b.life -= dt;
  }
  state.bullets = state.bullets.filter(b=>b.life>0);
}

function drawBullets(){
  if (!ctx.shots) return;
  
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

// ---------- Verificação de Colisões ----------
function checkCollisions() {
  for (let i = state.bullets.length - 1; i >= 0; i--) {
    const bullet = state.bullets[i];
    
    for (let j = state.entities.length - 1; j >= 0; j--) {
      const entity = state.entities[j];
      
      if (entity.kind !== 'asteroid') continue;
      
      const dx = bullet.x - entity.x;
      const dy = bullet.y - entity.y;
      const dz = bullet.z - entity.z;
      const distance = Math.sqrt(dx*dx + dy*dy + dz*dz);
      
      if (distance < entity.r) {
        if (settings.soundEnabled) {
          try { 
            const explosion = explosionAudio.cloneNode(); 
            explosion.volume = settings.sfxVolume; 
            explosion.play(); 
          } catch(e){
            console.log("Erro ao tocar som de explosão:", e);
          }
        }
        
        state.entities.splice(j, 1);
        state.bullets.splice(i, 1);
        spawnEntity();
        break;
      }
    }
  }
}

// ---------- Atualizar Painel de Waypoints ----------
function updateWaypointNav() {
  const navItems = document.querySelectorAll('.waypoint-item');
  
  state.waypoints.forEach((waypoint, index) => {
    if (navItems[index]) {
      const dx = waypoint.x - state.pos.x;
      const dy = waypoint.y - state.pos.y;
      const dz = waypoint.z - state.pos.z;
      const dist = Math.sqrt(dx*dx + dy*dy + dz*dz);
      
      const distDiv = navItems[index].querySelector('.waypoint-distance');
      if (distDiv) distDiv.textContent = dist.toFixed(0) + ' u';
      
      // Destacar waypoint selecionado
      if (state.targetWaypoint === waypoint) {
        navItems[index].classList.add('active');
      } else {
        navItems[index].classList.remove('active');
      }
    }
  });
}

// ---------- Mapa Holográfico 3D ----------
function drawHologram() {
  if (!ctx.hologram || !settings.showHologram) return;
  
  const c = ctx.hologram;
  const width = c.canvas.width;
  const height = c.canvas.height;
  
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
  
  // Desenhar título
  c.fillStyle = 'rgba(157, 78, 221, 0.9)';
  c.font = 'bold 14px monospace';
  c.textAlign = 'center';
  c.fillText('🌌 MAPA HOLOGRÁFICO 3D 🌌', width/2, 25);
  
  // Centro do holograma
  const centerX = width / 2;
  const centerY = height / 2;
  
  // Desenhar grade de fundo
  c.strokeStyle = 'rgba(157, 78, 221, 0.2)';
  c.lineWidth = 1;
  const gridSize = 20;
  
  // Grade horizontal
  for (let y = 40; y < height - 20; y += gridSize) {
    c.beginPath();
    c.moveTo(20, y);
    c.lineTo(width - 20, y);
    c.stroke();
  }
  
  // Grade vertical
  for (let x = 20; x < width - 20; x += gridSize) {
    c.beginPath();
    c.moveTo(x, 40);
    c.lineTo(x, height - 20);
    c.stroke();
  }
  
  // Desenhar eixos
  c.strokeStyle = 'rgba(157, 78, 221, 0.5)';
  c.lineWidth = 2;
  
  // Eixo X
  c.beginPath();
  c.moveTo(centerX - 80, centerY);
  c.lineTo(centerX + 80, centerY);
  c.stroke();
  
  // Eixo Z
  c.beginPath();
  c.moveTo(centerX, centerY - 60);
  c.lineTo(centerX, centerY + 60);
  c.stroke();
  
  // Desenhar Sol no centro
  c.fillStyle = 'rgba(255, 200, 0, 0.9)';
  c.beginPath();
  c.arc(centerX, centerY, 12, 0, Math.PI * 2);
  c.fill();
  
  // Desenhar contorno do Sol
  c.strokeStyle = 'rgba(255, 255, 255, 0.9)';
  c.lineWidth = 1;
  c.stroke();
  
  // Desenhar nome do Sol
  c.fillStyle = 'rgba(255, 255, 255, 0.9)';
  c.font = 'bold 11px monospace';
  c.textAlign = 'center';
  c.fillText('SOL', centerX, centerY - 20);
  
  // Desenhar órbitas e planetas
  state.planets.forEach(planet => {
    // Calcular posição no holograma
    const scale = 0.003; // Escala para o holograma
    const x = centerX + planet.x * scale;
    const z = centerY + planet.z * scale;
    
    // Desenhar órbita
    c.strokeStyle = 'rgba(157, 78, 221, 0.3)';
    c.lineWidth = 1;
    c.setLineDash([5, 3]);
    c.beginPath();
    c.arc(centerX, centerY, planet.orbitRadius * scale, 0, Math.PI * 2);
    c.stroke();
    c.setLineDash([]);
    
    // Desenhar planeta
    c.fillStyle = planet.color;
    c.beginPath();
    c.arc(x, z, 8, 0, Math.PI * 2);
    c.fill();
    
    // Desenhar contorno do planeta
    c.strokeStyle = 'rgba(255, 255, 255, 0.9)';
    c.lineWidth = 1;
    c.stroke();
    
    // Destacar se faz parte da constelação ativa
    if (state.activeConstellation && 
        state.constellations[state.activeConstellation].planets.includes(planet.name)) {
      c.strokeStyle = 'rgba(157, 78, 221, 0.9)';
      c.lineWidth = 2;
      c.stroke();
      
      // Desenhar linha conectando ao Sol
      c.beginPath();
      c.moveTo(centerX, centerY);
      c.lineTo(x, z);
      c.stroke();
    }
    
    // Desenhar nome do planeta
    c.fillStyle = 'rgba(255, 255, 255, 0.9)';
    c.font = '10px monospace';
    c.textAlign = 'center';
    c.fillText(planet.name, x, z - 15);
  });
  
  // Desenhar waypoints
  state.waypoints.forEach(waypoint => {
    // Calcular posição no holograma
    const scale = 0.003; // Escala para o holograma
    const x = centerX + waypoint.x * scale;
    const z = centerY + waypoint.z * scale;
    
    // Desenhar waypoint
    c.fillStyle = 'rgba(247, 37, 133, 0.9)';
    c.beginPath();
    c.arc(x, z, 6, 0, Math.PI * 2);
    c.fill();
    
    // Desenhar contorno do waypoint
    c.strokeStyle = 'rgba(255, 255, 255, 0.9)';
    c.lineWidth = 1;
    c.stroke();
    
    // Destacar se for o waypoint selecionado
    if (state.targetWaypoint === waypoint) {
      c.strokeStyle = 'rgba(247, 37, 133, 0.9)';
      c.lineWidth = 2;
      c.stroke();
      
      // Desenhar linha conectando à nave
      const shipX = centerX + state.pos.x * scale;
      const shipZ = centerY + state.pos.z * scale;
      
      c.beginPath();
      c.moveTo(shipX, shipZ);
      c.lineTo(x, z);
      c.stroke();
    }
    
    // Desenhar nome do waypoint
    c.fillStyle = 'rgba(255, 255, 255, 0.9)';
    c.font = '9px monospace';
    c.textAlign = 'center';
    c.fillText(waypoint.name, x, z - 12);
  });
  
  // Desenhar posição da nave
  const shipX = centerX + state.pos.x * 0.003;
  const shipZ = centerY + state.pos.z * 0.003;
  
  // Desenhar nave (triângulo)
  c.fillStyle = '#ff0';
  c.beginPath();
  c.moveTo(shipX, shipZ - 8);
  c.lineTo(shipX - 6, shipZ + 6);
  c.lineTo(shipX + 6, shipZ + 6);
  c.closePath();
  c.fill();
  
  // Desenhar contorno da nave
  c.strokeStyle = 'rgba(255, 255, 255, 0.9)';
  c.lineWidth = 1;
  c.stroke();
  
  // Desenhar texto da nave
  c.fillStyle = '#ff0';
  c.font = 'bold 10px monospace';
  c.textAlign = 'center';
  c.fillText('SUA NAVE', shipX, shipZ - 15);
  
  // Desenhar linha de direção se houver waypoint selecionado
  if (state.targetWaypoint) {
    const waypointX = centerX + state.targetWaypoint.x * 0.003;
    const waypointZ = centerY + state.targetWaypoint.z * 0.003;
    
    c.strokeStyle = 'rgba(247, 37, 133, 0.7)';
    c.lineWidth = 2;
    c.setLineDash([8, 4]);
    c.beginPath();
    c.moveTo(shipX, shipZ);
    c.lineTo(waypointX, waypointZ);
    c.stroke();
    c.setLineDash([]);
    
    // Texto indicando o destino
    c.fillStyle = 'rgba(247, 37, 133, 0.9)';
    c.font = 'bold 10px monospace';
    c.textAlign = 'center';
    const midX = (shipX + waypointX) / 2;
    const midZ = (shipZ + waypointZ) / 2;
    c.fillText(`DESTINO: ${state.targetWaypoint.name}`, midX, midZ - 10);
  }
  
  // Desenhar legenda
  c.fillStyle = 'rgba(255, 255, 255, 0.8)';
  c.font = '10px monospace';
  c.textAlign = 'left';
  c.fillText('🚀 Sua posição amarela', 10, height - 25);
  c.fillText('☀️ Sol e planetas coloridos', 10, height - 15);
  c.fillText('📍 Waypoints rosa', 10, height - 5);
}

// ---------- HUD ----------
function updateHUD(){
  const spdElement = document.getElementById('spd');
  const dirElement = document.getElementById('dir');
  const zoomElement = document.getElementById('zoom');
  const coordsElement = document.getElementById('coords');
  const needle = document.getElementById('needle');
  
  if (spdElement) spdElement.textContent = state.speed.toFixed(1);
  if (dirElement) dirElement.textContent = `${(state.yaw*57.3|0)}°`;
  if (zoomElement) zoomElement.textContent = state.zoom.toFixed(2) + 'x';
  if (coordsElement) coordsElement.textContent = `${state.pos.x.toFixed(0)}, ${state.pos.y.toFixed(0)}, ${state.pos.z.toFixed(0)}`;
  if (needle) needle.style.transform = `translate(-50%, -100%) rotate(${clamp(state.speed*3,0,240)}deg)`;
}

// ---------- Clique/Tap para atirar ----------
wrap.addEventListener('click', (e)=>{
  shoot();
});

// Carregar imagens e iniciar jogo
Promise.all([
  loadImage('nebula', IMG.nebula),
  loadImage('stars',  IMG.stars),
  loadImage('sol', IMG.sol),
  loadImage('terra', IMG.terra),
  loadImage('marte', IMG.marte),
  loadImage('jupter', IMG.jupter),
  loadImage('sartuno', IMG.sartuno),
  loadImage('netuno', IMG.netuno),
  loadImage('venus', IMG.venus),
  loadImage('ship', IMG.ship),
  loadImage('asteroid', IMG.asteroid),
]).then(()=>{
  console.log("Imagens carregadas, iniciando jogo com navegação em primeira pessoa...");
  init();
}).catch(error => {
  console.error("Erro ao carregar imagens:", error);
  // Iniciar mesmo sem imagens
  init();
});
</script>
</body>
</html>