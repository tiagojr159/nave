<?php
// Sistema de Navegação Espacial com Recursos Avançados
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navegação Sistema Solar Avançado</title>
    <style>
        :root { 
            --hud: #0ff; 
            --bg:#000; 
            --accent:#1e90ff; 
            --danger:#ff3b3b; 
            --hologram:#9d4edd; 
            --waypoint:#f72585; 
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 100%;
            height: 100vh;
            overflow: hidden;
            background: url('../images/universo.jpg') center/cover no-repeat;
            font-family: Arial, sans-serif;
        }

        #gameContainer {
            width: 100%;
            height: 100%;
            position: relative;
            background: linear-gradient(135deg, #000814 0%, #001d3d 50%, #003566 100%);
            overflow: hidden;
            perspective: 1000px;
        }

        #viewport {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
        }

        .planet {
            position: absolute;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }

        .planet.sol {
            box-shadow: 0 0 60px rgba(255, 200, 0, 0.8);
        }

        /* HUD Principal */
        #hud {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            z-index: 100;
            background: rgba(0, 0, 0, 0.7);
            padding: 15px;
            border: 2px solid #00ff00;
        }

        .hud-info {
            margin: 8px 0;
            font-size: 14px;
        }

        /* Radar */
        #radar {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 150px;
            height: 150px;
            border: 2px solid #00ff00;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 50%;
            z-index: 100;
        }

        #radarCanvas {
            width: 100%;
            height: 100%;
        }

        /* Mira */
        .crosshair {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            pointer-events: none;
            z-index: 50;
        }

        .crosshair::before {
            content: '';
            position: absolute;
            width: 2px;
            height: 15px;
            background: #00ff00;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        .crosshair::after {
            content: '';
            position: absolute;
            width: 15px;
            height: 2px;
            background: #00ff00;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        #instructions {
            position: absolute;
            bottom: 20px;
            left: 20px;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.7);
            padding: 15px;
            border: 2px solid #00ff00;
            font-size: 12px;
            z-index: 100;
        }

        .planetName {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #ffff00;
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.8);
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        /* Novos componentes */
        
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
        
        /* Botões adicionais */
        .btn {
            pointer-events: auto; 
            cursor: pointer; 
            user-select: none;
            background: rgba(30,144,255,.15); 
            border: 1px solid rgba(30,144,255,.4);
            padding: 10px 14px; 
            border-radius: 12px; 
            font-weight: 700; 
            color: #cfeaff;
            backdrop-filter: blur(4px);
            margin: 5px;
        }
        
        .btn:active { 
            transform: scale(.98); 
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
    <div id="gameContainer">
        <div id="viewport"></div>
        <div class="crosshair"></div>
        
        <!-- Efeito de Velocidade -->
        <div class="speed-effect" id="speedEffect"></div>
        
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
        
        <!-- HUD Principal -->
        <div id="hud">
            <div class="hud-info">POSIÇÃO: <span id="posX">0</span>, <span id="posY">0</span>, <span id="posZ">0</span></div>
            <div class="hud-info">CÂMERA: <span id="camX">0</span>°, <span id="camY">0</span>°</div>
            <div class="hud-info">ALVO: <span id="target">---</span></div>
            <div class="hud-info">DISTÂNCIA: <span id="distance">---</span> km</div>
            <div class="hud-info">VELOCIDADE: <span id="speed">0</span> km/s</div>
            <div class="hud-info">FPS: <span id="fps">---</span></div>
            
            <div style="margin-top: 10px;">
                <button class="btn" id="btnCenter">Recentrar Câmera</button>
                <button class="btn" id="btnSpawn">Spawn Asteroide</button>
                <button class="btn" id="btnClear">Limpar Tiros</button>
                <button class="btn" id="btnWarp">Warp Rápido</button>
                <button class="btn" id="btnSettings">Configurações</button>
            </div>
        </div>

        <canvas id="radarCanvas"></canvas>

        <div id="instructions">
            ↑↓←→: Câmera | W/A/S/D: Movimento | ESPAÇO: Acelerar | SHIFT: Turbo
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

        class SolarSystemGame {
            constructor() {
                this.viewport = document.getElementById('viewport');
                this.container = document.getElementById('gameContainer');
                
                // Posição da nave (player)
                this.playerPos = { x: 0, y: 0, z: 0 };
                this.playerRot = { x: 0, y: 0 };
                this.playerVel = { x: 0, y: 0, z: 0 };
                this.speed = 0;
                this.maxSpeed = 500;
                
                // Câmera
                this.cameraRot = { x: 0, y: 0 };
                
                // Planetas com suas posições, tamanhos e imagens
                this.planets = [
                    { name: 'SOL', x: 50000, y: 50000, z: 50000, size: 2000, img: '../images/sol.png', type: 'star' },
                    { name: 'MERCÚRIO', x: 55000, y: 50000, z: 50000, size: 300, img: '../images/sol.png' },
                    { name: 'VÊNUS', x: 58000, y: 50000, z: 50000, size: 600, img: '../images/venus.png' },
                    { name: 'TERRA', x: 62000, y: 50000, z: 50000, size: 600, img: '../images/terra.png' },
                    { name: 'MARTE', x: 68000, y: 50000, z: 50000, size: 400, img: '../images/marte.png' },
                    { name: 'JÚPITER', x: 85000, y: 50000, z: 50000, size: 1200, img: '../images/jupter.png' },
                    { name: 'SATURNO', x: 105000, y: 50000, z: 50000, size: 1100, img: '../images/sartuno.png' },
                    { name: 'GANIMEDES', x: 70000, y: 40000, z: 50000, size: 400, img: '../images/ganimedes.png' },
                    { name: 'CERES', x: 30000, y: 50000, z: 50000, size: 300, img: '../images/ceres.png' },
                ];
                
                this.planetElements = new Map();
                this.radarCanvas = document.getElementById('radarCanvas');
                this.radarCtx = this.radarCanvas.getContext('2d');
                this.radarCanvas.width = 150;
                this.radarCanvas.height = 150;
                
                this.keys = {};
                this.setupEventListeners();
                this.createPlanets();
                this.initAdditionalFeatures();
                this.gameLoop();
            }
            
            initAdditionalFeatures() {
                // Carregar configurações
                loadSettings();
                
                // Configurar eventos de configurações
                setupSettingsEvents();
                
                // Criar waypoints
                this.createWaypoints();
                
                // Criar painel de navegação
                this.createWaypointNav();
                
                // Inicializar holograma
                this.initHologram();
                
                // Inicializar scanner
                this.initScanner();
                
                // Inicializar constelações
                this.initConstellations();
                
                // Inicializar estado do jogo
                state.entities = [];
                state.bullets = [];
                state.nearestPlanet = null;
                state.nearestDist = Infinity;
                state.keysPressed = {};
                state.warpMode = false;
                state.boostEnergy = 100;
                state.targetWaypoint = null;
                state.activeConstellation = null;
                state.scannerObjects = [];
                
                // Criar entidades iniciais
                for (let i = 0; i < 20; i++) {
                    this.spawnEntity();
                }
                
                // Inicializar áudio
                this.initAudio();
            }
            
            // ---------- Criar Waypoints ----------
            createWaypoints() {
                state.waypoints = [
                    { name: 'Estação Espacial', x: 50000, y: 52000, z: 50000, type: 'station' },
                    { name: 'Cinturão de Asteroides', x: 30000, y: 50000, z: 50000, type: 'asteroids' },
                    { name: 'Anel de Saturno', x: 105000, y: 50000, z: 50000, type: 'planet' },
                    { name: 'Polo Norte de Marte', x: 68000, y: 51000, z: 50000, type: 'planet' },
                    { name: 'Laboratório de Pesquisa', x: 62000, y: 48000, z: 50000, type: 'lab' }
                ];
            }
            
            // ---------- Painel de Navegação por Waypoints ----------
            createWaypointNav() {
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
                        this.selectWaypoint(index);
                    });
                    
                    navContainer.appendChild(item);
                });
            }
            
            // ---------- Selecionar Waypoint ----------
            selectWaypoint(index) {
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
                this.showFeedback(`Waypoint selecionado: ${state.targetWaypoint.name}`);
                
                // Atualizar HUD
                const targetElement = document.getElementById('target');
                if (targetElement) targetElement.textContent = state.targetWaypoint.name;
            }
            
            // ---------- Inicializar Holograma ----------
            initHologram() {
                const hologramCanvas = document.getElementById('hologram');
                if (!hologramCanvas) return;
                
                const containerWidth = window.innerWidth > 900 ? 326 : 256;
                const containerHeight = window.innerWidth > 900 ? 220 : 170;
                
                hologramCanvas.width = containerWidth;
                hologramCanvas.height = containerHeight;
                ctx.hologram = hologramCanvas.getContext('2d');
            }
            
            // ---------- Inicializar Scanner ----------
            initScanner() {
                // Adicionar objetos ao scanner
                this.updateScannerObjects();
            }
            
            // ---------- Atualizar Objetos do Scanner ----------
            updateScannerObjects() {
                state.scannerObjects = [];
                
                // Adicionar planetas próximos
                this.planets.forEach(planet => {
                    const dx = planet.x - this.playerPos.x;
                    const dy = planet.y - this.playerPos.y;
                    const dz = planet.z - this.playerPos.z;
                    const distance = Math.sqrt(dx*dx + dy*dy + dz*dz);
                    
                    if (distance < 30000) {
                        const angle = Math.atan2(dz, dx);
                        state.scannerObjects.push({
                            name: planet.name,
                            distance: distance,
                            angle: angle,
                            type: 'planet',
                            size: planet.size
                        });
                    }
                });
                
                // Adicionar waypoints próximos
                state.waypoints.forEach(waypoint => {
                    const dx = waypoint.x - this.playerPos.x;
                    const dy = waypoint.y - this.playerPos.y;
                    const dz = waypoint.z - this.playerPos.z;
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
                this.renderScannerObjects();
            }
            
            // ---------- Renderizar Objetos no Scanner ----------
            renderScannerObjects() {
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
            initConstellations() {
                state.constellations = {
                    orion: { name: "Órion", planets: ["Sol", "Vênus", "Terra"] },
                    ursa: { name: "Ursa Maior", planets: ["Marte", "Júpiter", "Saturno"] },
                    lyra: { name: "Lira", planets: ["Netuno", "Vênus", "Terra"] },
                    cygnus: { name: "Cisne", planets: ["Júpiter", "Saturno", "Netuno"] },
                    scorpius: { name: "Escorpião", planets: ["Sol", "Marte", "Júpiter"] },
                    aquarius: { name: "Aquário", planets: ["Saturno", "Netuno", "Vênus"] }
                };
                
                const constellationItems = document.querySelectorAll('.constellation-item');
                
                constellationItems.forEach(item => {
                    item.addEventListener('click', () => {
                        const constellation = item.dataset.constellation;
                        this.selectConstellation(constellation);
                    });
                });
            }
            
            // ---------- Selecionar Constelação ----------
            selectConstellation(constellation) {
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
                this.showFeedback(`Constelação selecionada: ${constellationName}`);
                
                // Destacar planetas da constelação no holograma
                this.updateHologramHighlight();
            }
            
            // ---------- Atualizar Destaque no Holograma ----------
            updateHologramHighlight() {
                // Esta função será chamada durante a renderização do holograma
                // para destacar os planetas da constelação ativa
            }
            
            // ---------- Inicializar Áudio ----------
            initAudio() {
                // Implementar inicialização de áudio se necessário
            }
            
            // ---------- Spawns ----------
            spawnEntity() {
                state.entities.push({
                    kind: 'asteroid',
                    x: Math.random() * 100000,
                    y: Math.random() * 100000,
                    z: Math.random() * 100000,
                    vx: Math.random() * 0.2 - 0.1,
                    vy: Math.random() * 0.2 - 0.1,
                    vz: Math.random() * 0.2 - 0.1,
                    r: Math.random() * 60 + 20,
                    rot: Math.random() * Math.PI * 2,
                    rotSpd: Math.random() * 0.02 - 0.01,
                    randomVx: Math.random() * 0.04 - 0.02,
                    randomVy: Math.random() * 0.04 - 0.02,
                    randomVz: Math.random() * 0.04 - 0.02
                });
            }
            
            // ---------- Mostrar feedback visual ----------
            showFeedback(message) {
                const feedback = document.getElementById('feedback');
                if (feedback) {
                    feedback.textContent = message;
                    feedback.style.opacity = 1;
                    setTimeout(() => {
                        feedback.style.opacity = 0;
                    }, 1500);
                }
            }
            
            // ---------- Atualizar barra de velocidade ----------
            updateSpeedBar() {
                const speedBar = document.getElementById('speedBar');
                if (speedBar) {
                    const speedPercent = Math.min(100, (this.speed / this.maxSpeed) * 100);
                    speedBar.style.width = speedPercent + '%';
                }
            }
            
            // ---------- Atualizar efeito de velocidade ----------
            updateSpeedEffect() {
                const speedEffect = document.getElementById('speedEffect');
                if (speedEffect) {
                    const opacity = Math.min(0.7, this.speed / this.maxSpeed * 0.7);
                    speedEffect.style.opacity = opacity;
                }
            }
            
            setupEventListeners() {
                document.addEventListener('keydown', (e) => {
                    this.keys[e.key.toLowerCase()] = true;
                    state.keysPressed[e.code] = true;
                    
                    // Aceleração - W ou Setas Cima (mover para frente)
                    if (e.code === 'KeyW' || e.code === 'ArrowUp') {
                        const dir = this.forwardVector();
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
                    if (e.code === 'KeyS' || e.code === 'ArrowDown') {
                        state.vel.x *= 0.85;
                        state.vel.y *= 0.85;
                        state.vel.z *= 0.85;
                    }
                    
                    // Controles verticais
                    if (e.code === 'KeyQ') state.vel.y -= 0.3;
                    if (e.code === 'KeyE') state.vel.y += 0.3;
                    
                    // Atirar
                    if (e.code === 'Space') this.shoot();
                    
                    // Abrir/fechar configurações
                    if (e.code === 'Escape') {
                        const settingsPanel = document.getElementById('settingsPanel');
                        settingsPanel.classList.toggle('active');
                    }
                });
                
                document.addEventListener('keyup', (e) => {
                    this.keys[e.key.toLowerCase()] = false;
                    state.keysPressed[e.code] = false;
                });
                
                document.addEventListener('mousemove', (e) => {
                    const dx = (e.clientX - window.innerWidth / 2) * settings.mouseSensitivity;
                    const dy = (e.clientY - window.innerHeight / 2) * settings.mouseSensitivity;
                    
                    // Aplicar inversão do eixo Y se configurado
                    const pitchMultiplier = settings.invertY ? -1 : 1;
                    
                    this.cameraRot.y += dx;
                    this.cameraRot.x += dy * pitchMultiplier;
                    
                    this.cameraRot.x = Math.max(-Math.PI / 2, Math.min(Math.PI / 2, this.cameraRot.x));
                });
                
                // Controle com setas
                document.addEventListener('keydown', (e) => {
                    const rotSpeed = 0.05;
                    if (e.key === 'ArrowUp') this.cameraRot.x = Math.max(-Math.PI / 2, this.cameraRot.x - rotSpeed);
                    if (e.key === 'ArrowDown') this.cameraRot.x = Math.min(Math.PI / 2, this.cameraRot.x + rotSpeed);
                    if (e.key === 'ArrowLeft') this.cameraRot.y -= rotSpeed;
                    if (e.key === 'ArrowRight') this.cameraRot.y += rotSpeed;
                });
                
                // Botões UI
                document.getElementById('btnCenter').onclick = () => {
                    this.cameraRot.x = 0;
                    this.cameraRot.y = 0;
                    this.showFeedback("VISÃO RECENTRADA");
                };
                
                document.getElementById('btnSpawn').onclick = () => {
                    this.spawnEntity();
                    this.showFeedback("ASTEROIDE CRIADO");
                };
                
                document.getElementById('btnClear').onclick = () => {
                    state.bullets.length = 0;
                    this.showFeedback("TIROS LIMPOS");
                };
                
                document.getElementById('btnWarp').onclick = () => {
                    state.warpMode = !state.warpMode;
                    if (state.warpMode) {
                        this.maxSpeed = 1000;
                        this.showFeedback("MODO WARP ATIVADO");
                    } else {
                        this.maxSpeed = 500;
                        this.showFeedback("MODO WARP DESATIVADO");
                    }
                    document.getElementById('btnWarp').textContent = state.warpMode ? 'Warp: ON' : 'Warp Rápido';
                    document.getElementById('btnWarp').style.background = state.warpMode ? 'rgba(255,100,0,.3)' : 'rgba(30,144,255,.15)';
                };
                
                document.getElementById('btnSettings').onclick = () => {
                    const settingsPanel = document.getElementById('settingsPanel');
                    settingsPanel.classList.toggle('active');
                };
                
                // Botões de controle
                document.getElementById('brakeBtn').onclick = () => {
                    state.vel.x *= 0.5;
                    state.vel.y *= 0.5;
                    state.vel.z *= 0.5;
                    this.showFeedback("FREIO APLICADO");
                };
                
                document.getElementById('boostBtn').onclick = () => {
                    if (state.boostEnergy > 20) {
                        const dir = this.forwardVector();
                        state.vel.x += dir.x * 3;
                        state.vel.y += dir.y * 3;
                        state.vel.z += dir.z * 3;
                        state.boostEnergy -= 30;
                        this.showFeedback("TURBO ATIVADO");
                    } else {
                        this.showFeedback("TURBO SEM ENERGIA");
                    }
                };
                
                // Clique/Tap para atirar
                this.container.addEventListener('click', (e) => {
                    this.shoot();
                });
            }
            
            // ---------- Vetores ----------
            forwardVector() {
                const cosP = Math.cos(this.cameraRot.x), sinP = Math.sin(this.cameraRot.x);
                const cosY = Math.cos(this.cameraRot.y), sinY = Math.sin(this.cameraRot.y);
                return { x: sinY * cosP, y: sinP, z: -cosY * cosP };
            }
            
            // ---------- Disparos ----------
            shoot() {
                if (!settings.soundEnabled) return;
                
                // Implementar som de tiro se necessário
                
                const speed = 22;
                const dir = this.forwardVector();
                state.bullets.push({
                    x: this.playerPos.x, 
                    y: this.playerPos.y, 
                    z: this.playerPos.z,
                    vx: dir.x * speed, 
                    vy: dir.y * speed, 
                    vz: dir.z * speed,
                    life: 120
                });
            }
            
            createPlanets() {
                this.planets.forEach(planet => {
                    const el = document.createElement('div');
                    el.className = 'planet';
                    if (planet.type === 'star') el.classList.add('sol');
                    el.style.backgroundImage = `url('${planet.img}')`;
                    el.dataset.name = planet.name;
                    this.viewport.appendChild(el);
                    this.planetElements.set(planet.name, el);
                });
            }
            
            updatePlanets() {
                this.planets.forEach(planet => {
                    // Posição relativa à câmera
                    const relPos = {
                        x: planet.x - this.playerPos.x,
                        y: planet.y - this.playerPos.y,
                        z: planet.z - this.playerPos.z
                    };
                    
                    // Aplicar rotação da câmera
                    const rotated = this.rotateVector(relPos, this.cameraRot);
                    
                    const el = this.planetElements.get(planet.name);
                    
                    // Se o planeta está atrás, não renderizar
                    if (rotated.z < 100) {
                        el.style.display = 'none';
                        return;
                    }
                    
                    // Projeção perspectiva
                    const fov = 800;
                    const scale = fov / rotated.z;
                    const screenX = (rotated.x * scale) + (window.innerWidth / 2);
                    const screenY = (rotated.y * scale) + (window.innerHeight / 2);
                    const screenSize = planet.size * scale;
                    
                    // Clipping
                    if (screenX + screenSize < 0 || screenX - screenSize > window.innerWidth ||
                        screenY + screenSize < 0 || screenY - screenSize > window.innerHeight) {
                        el.style.display = 'none';
                        return;
                    }
                    
                    el.style.display = 'block';
                    el.style.left = (screenX - screenSize / 2) + 'px';
                    el.style.top = (screenY - screenSize / 2) + 'px';
                    el.style.width = screenSize + 'px';
                    el.style.height = screenSize + 'px';
                });
            }
            
            rotateVector(v, rot) {
                let x = v.x, y = v.y, z = v.z;
                
                // Rotação em Y
                const cosY = Math.cos(rot.y);
                const sinY = Math.sin(rot.y);
                let x1 = x * cosY - z * sinY;
                let z1 = x * sinY + z * cosY;
                
                // Rotação em X
                const cosX = Math.cos(rot.x);
                const sinX = Math.sin(rot.x);
                let y1 = y * cosX - z1 * sinX;
                let z2 = y * sinX + z1 * cosX;
                
                return { x: x1, y: y1, z: z2 };
            }
            
            updateInput() {
                const moveSpeed = 2;
                let moved = false;
                
                // Verificar teclas pressionadas
                const turboActive = state.keysPressed['ShiftLeft'] || state.keysPressed['ShiftRight'];
                const turboMultiplier = turboActive ? 3 : 1;
                
                // Gerenciar energia do turbo
                if (turboActive && state.boostEnergy > 0) {
                    state.boostEnergy -= 0.8;
                } else {
                    state.boostEnergy = Math.min(100, state.boostEnergy + 0.2);
                }
                
                // Movimento baseado em câmera
                if (this.keys['w']) {
                    this.playerPos.x += Math.sin(this.cameraRot.y) * moveSpeed * turboMultiplier;
                    this.playerPos.z += Math.cos(this.cameraRot.y) * moveSpeed * turboMultiplier;
                    moved = true;
                }
                if (this.keys['s']) {
                    this.playerPos.x -= Math.sin(this.cameraRot.y) * moveSpeed * turboMultiplier;
                    this.playerPos.z -= Math.cos(this.cameraRot.y) * moveSpeed * turboMultiplier;
                    moved = true;
                }
                if (this.keys['a']) {
                    this.playerPos.x -= Math.cos(this.cameraRot.y) * moveSpeed * turboMultiplier;
                    this.playerPos.z += Math.sin(this.cameraRot.y) * moveSpeed * turboMultiplier;
                    moved = true;
                }
                if (this.keys['d']) {
                    this.playerPos.x += Math.cos(this.cameraRot.y) * moveSpeed * turboMultiplier;
                    this.playerPos.z -= Math.sin(this.cameraRot.y) * moveSpeed * turboMultiplier;
                    moved = true;
                }
                
                if (this.keys[' ']) {
                    this.playerPos.y += moveSpeed * turboMultiplier;
                }
                if (this.keys['shift']) {
                    this.playerPos.y -= moveSpeed * turboMultiplier;
                }
                
                // Atualizar velocidade baseada no estado
                this.playerPos.x += state.vel.x * 0.1;
                this.playerPos.y += state.vel.y * 0.1;
                this.playerPos.z += state.vel.z * 0.1;
                
                // Aplicar inércia
                state.vel.x *= 0.92; 
                state.vel.y *= 0.92; 
                state.vel.z *= 0.92;
                
                // Calcular velocidade atual
                this.speed = Math.hypot(state.vel.x, state.vel.y, state.vel.z) * 10;
                
                if (moved || Math.abs(state.vel.x) > 0.1 || Math.abs(state.vel.y) > 0.1 || Math.abs(state.vel.z) > 0.1) {
                    this.speed = Math.min(this.speed + 5, this.maxSpeed);
                } else {
                    this.speed = Math.max(this.speed - 10, 0);
                }
                
                // Limitar movimento na área
                this.playerPos.x = Math.max(0, Math.min(100000, this.playerPos.x));
                this.playerPos.y = Math.max(0, Math.min(100000, this.playerPos.y));
                this.playerPos.z = Math.max(0, Math.min(100000, this.playerPos.z));
            }
            
            updateHUD() {
                document.getElementById('posX').textContent = Math.round(this.playerPos.x);
                document.getElementById('posY').textContent = Math.round(this.playerPos.y);
                document.getElementById('posZ').textContent = Math.round(this.playerPos.z);
                document.getElementById('camX').textContent = Math.round(this.cameraRot.y * 180 / Math.PI);
                document.getElementById('camY').textContent = Math.round(this.cameraRot.x * 180 / Math.PI);
                document.getElementById('speed').textContent = Math.round(this.speed / 100);
                
                // Encontrar planeta mais próximo
                let closestPlanet = null;
                let closestDist = Infinity;
                
                this.planets.forEach(planet => {
                    const dx = planet.x - this.playerPos.x;
                    const dy = planet.y - this.playerPos.y;
                    const dz = planet.z - this.playerPos.z;
                    const dist = Math.sqrt(dx * dx + dy * dy + dz * dz);
                    
                    if (dist < closestDist) {
                        closestDist = dist;
                        closestPlanet = planet;
                    }
                });
                
                if (closestPlanet) {
                    document.getElementById('target').textContent = state.targetWaypoint ? state.targetWaypoint.name : closestPlanet.name;
                    document.getElementById('distance').textContent = Math.round(closestDist / 1000);
                }
            }
            
            updateRadar() {
                const ctx = this.radarCtx;
                const w = this.radarCanvas.width;
                const h = this.radarCanvas.height;
                
                // Fundo
                ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
                ctx.fillRect(0, 0, w, h);
                
                // Grade
                ctx.strokeStyle = 'rgba(0, 255, 0, 0.2)';
                ctx.beginPath();
                for (let i = 0; i <= 4; i++) {
                    const y = (h / 4) * i;
                    ctx.moveTo(0, y);
                    ctx.lineTo(w, y);
                    const x = (w / 4) * i;
                    ctx.moveTo(x, 0);
                    ctx.lineTo(x, h);
                }
                ctx.stroke();
                
                const radarScale = 1000;
                
                // Desenhar planetas
                this.planets.forEach(planet => {
                    const relX = (planet.x - this.playerPos.x) / radarScale;
                    const relY = (planet.z - this.playerPos.z) / radarScale;
                    
                    const radarX = (w / 2) + relX * (w / 8);
                    const radarY = (h / 2) + relY * (h / 8);
                    
                    if (planet.type === 'star') {
                        ctx.fillStyle = '#ffff00';
                        ctx.globalAlpha = 0.8;
                    } else {
                        ctx.fillStyle = '#ff6600';
                        ctx.globalAlpha = 0.6;
                    }
                    ctx.beginPath();
                    ctx.arc(radarX, radarY, 3, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.globalAlpha = 1;
                });
                
                // Desenhar waypoints
                state.waypoints.forEach(waypoint => {
                    const relX = (waypoint.x - this.playerPos.x) / radarScale;
                    const relY = (waypoint.z - this.playerPos.z) / radarScale;
                    
                    const radarX = (w / 2) + relX * (w / 8);
                    const radarY = (h / 2) + relY * (h / 8);
                    
                    ctx.fillStyle = '#f72585';
                    ctx.globalAlpha = 0.8;
                    ctx.beginPath();
                    ctx.arc(radarX, radarY, 2, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.globalAlpha = 1;
                });
                
                // Centro (nave)
                ctx.fillStyle = '#00ff00';
                ctx.beginPath();
                ctx.arc(w / 2, h / 2, 2, 0, Math.PI * 2);
                ctx.fill();
                
                // Direção
                ctx.strokeStyle = '#00ff00';
                ctx.beginPath();
                const dirX = Math.sin(this.cameraRot.y) * 20;
                const dirY = Math.cos(this.cameraRot.y) * 20;
                ctx.moveTo(w / 2, h / 2);
                ctx.lineTo(w / 2 + dirX, h / 2 + dirY);
                ctx.stroke();
            }
            
            // ---------- Mapa Holográfico 3D ----------
            drawHologram() {
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
                
                // Desenhar planetas
                this.planets.forEach(planet => {
                    // Calcular posição no holograma
                    const scale = 0.003; // Escala para o holograma
                    const x = centerX + (planet.x - 50000) * scale;
                    const z = centerY + (planet.z - 50000) * scale;
                    
                    // Desenhar planeta
                    if (planet.type === 'star') {
                        c.fillStyle = 'rgba(255, 200, 0, 0.9)';
                    } else {
                        c.fillStyle = 'rgba(100, 150, 255, 0.9)';
                    }
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
                    const x = centerX + (waypoint.x - 50000) * scale;
                    const z = centerY + (waypoint.z - 50000) * scale;
                    
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
                        const shipX = centerX + (this.playerPos.x - 50000) * scale;
                        const shipZ = centerY + (this.playerPos.z - 50000) * scale;
                        
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
                const shipX = centerX + (this.playerPos.x - 50000) * 0.003;
                const shipZ = centerY + (this.playerPos.z - 50000) * 0.003;
                
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
                    const waypointX = centerX + (state.targetWaypoint.x - 50000) * 0.003;
                    const waypointZ = centerY + (state.targetWaypoint.z - 50000) * 0.003;
                    
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
            
            // ---------- Atualizar Painel de Waypoints ----------
            updateWaypointNav() {
                const navItems = document.querySelectorAll('.waypoint-item');
                
                state.waypoints.forEach((waypoint, index) => {
                    if (navItems[index]) {
                        const dx = waypoint.x - this.playerPos.x;
                        const dy = waypoint.y - this.playerPos.y;
                        const dz = waypoint.z - this.playerPos.z;
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
            
            // ---------- Entidades ----------
            updateEntities(dt) {
                for (const e of state.entities) {
                    e.x += e.vx * dt * 10;
                    e.y += e.vy * dt * 10;
                    e.z += e.vz * dt * 10;
                    
                    if (e.kind === 'asteroid') {
                        e.x += e.randomVx * dt * 10;
                        e.y += e.randomVy * dt * 10;
                        e.z += e.randomVz * dt * 10;
                        
                        const distToPlayer = Math.hypot(e.x - this.playerPos.x, e.y - this.playerPos.y, e.z - this.playerPos.z);
                        if (distToPlayer > 100000) {
                            e.x = Math.random() * 100000;
                            e.y = Math.random() * 100000;
                            e.z = Math.random() * 100000;
                        }
                    }
                    
                    e.rot += e.rotSpd * dt * 10;
                }
            }
            
            // ---------- Tiros ----------
            updateBullets(dt) {
                for (const b of state.bullets) {
                    b.x += b.vx * dt; 
                    b.y += b.vy * dt; 
                    b.z += b.vz * dt; 
                    b.life -= dt;
                }
                state.bullets = state.bullets.filter(b => b.life > 0);
            }
            
            // ---------- Verificação de Colisões ----------
            checkCollisions() {
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
                            // Implementar som de explosão se necessário
                            
                            state.entities.splice(j, 1);
                            state.bullets.splice(i, 1);
                            this.spawnEntity();
                            break;
                        }
                    }
                }
            }
            
            // ---------- FPS ----------
            updateFPS() {
                if (!this.lastTime) this.lastTime = performance.now();
                if (!this.frameCount) this.frameCount = 0;
                if (!this.fpsTime) this.fpsTime = 0;
                
                const now = performance.now();
                const dt = Math.min(33, now - this.lastTime) / 16.666;
                this.lastTime = now;
                
                this.frameCount++;
                this.fpsTime += dt * 16.666;
                
                if (this.fpsTime >= 500) {
                    const fpsElement = document.getElementById('fps');
                    if (fpsElement) {
                        fpsElement.textContent = Math.round(this.frameCount * 1000 / this.fpsTime);
                    }
                    this.frameCount = 0;
                    this.fpsTime = 0;
                }
            }
            
            gameLoop() {
                this.updateInput();
                this.updatePlanets();
                this.updateHUD();
                this.updateRadar();
                this.updateSpeedBar();
                this.updateSpeedEffect();
                this.updateScannerObjects();
                this.updateWaypointNav();
                this.drawHologram();
                this.updateEntities(0.1);
                this.updateBullets(0.1);
                this.checkCollisions();
                this.updateFPS();
                
                requestAnimationFrame(() => this.gameLoop());
            }
        }
        
        // Estado global para compartilhar entre componentes
        const state = {
            t: 0,
            pos: { x: 0, y: 0, z: 0 },
            vel: { x: 0, y: 0, z: 0 },
            yaw: 0,
            pitch: 0,
            speed: 0,
            baseSpeed: 5.0,
            maxSpeed: 50.0,
            zoom: 1.0,
            bullets: [],
            entities: [],
            planets: [],
            mouse: { x: 0, y: 0 },
            fps: 0,
            maxZoom: 10.0,
            minZoom: 0.1,
            warpMode: false,
            universeSize: 120000,
            nearestPlanet: null,
            nearestDist: Infinity,
            keysPressed: {},
            sun: { x: 50000, y: 50000, z: 50000, r: 2000, name: 'Sol' },
            
            // Sistema de navegação em primeira pessoa
            acceleration: 0.3,
            deceleration: 0.15,
            turboMultiplier: 3.0,
            cameraRotationSpeed: 0.05,
            maxCameraRotationSpeed: 0.2,
            inertia: 0.92,
            currentMaxSpeed: 30.0,
            boostEnergy: 100,
            boostRechargeRate: 0.2,
            boostConsumptionRate: 0.8,
            
            // Novo sistema de navegação
            hologramRotation: { x: 0, y: 0 },
            targetWaypoint: null,
            waypoints: [],
            constellations: {},
            activeConstellation: null,
            scannerObjects: []
        };
        
        // Contexto global para holograma
        const ctx = {};
        
        window.addEventListener('load', () => {
            new SolarSystemGame();
        });
    </script>
</body>
</html>