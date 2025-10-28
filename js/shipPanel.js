// shipPanel.js - Camada do painel da nave
(function () {
    let ultimoSalvamento = 0; // guarda o timestamp do último envio

    console.log("shipPanel.js carregado");

    // Função para inicializar o painel
    function initShipPanel() {
        console.log("Inicializando painel da nave");

        // Elementos do painel
        const hudVel = document.getElementById('vel');
        const hudHdg = document.getElementById('hdg');
        const hudPos = document.getElementById('pos');
        const hudTgt = document.getElementById('tgt');
        const hudEnergy = document.getElementById('energy');

        // Verificar se os elementos existem
        if (!hudVel || !hudHdg || !hudPos || !hudTgt || !hudEnergy) {
            console.error("Elementos do HUD não encontrados");
            return false;
        }

        console.log("Elementos do HUD encontrados com sucesso");

        // 🔹 Função para salvar posição no backend
        function salvarPosicao(x, y) {
            fetch('db/salvar_posicao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `posicao_x=${Math.round(x)}&posicao_y=${Math.round(y)}`
            })
                .then(r => r.json())
                .then(d => console.log('🛰️ posição salva:', d))
                .catch(e => console.error('❌ erro ao salvar posição:', e));
        }

        function updateHUD(nearestInfo) {
            hudVel.textContent = ship.vel.toFixed(0);
            hudHdg.textContent = ((ship.heading % 360 + 360) % 360).toFixed(0) + '°';
            hudPos.textContent = `${ship.x.toFixed(0)},${ship.y.toFixed(0)}`;
            hudEnergy.textContent = Math.round(ship.boostEnergy) + '%';

            // 🔹 só salva se passaram 10s desde o último salvamento
            const agora = Date.now();
            if (agora - ultimoSalvamento >= 10000) {
                ultimoSalvamento = agora;
                salvarPosicao(ship.x, ship.y);
            }
        }

        // Configurar controles da nave
        function setupControls() {
            const brakeBtn = document.getElementById('brakeBtn');
            const boostBtn = document.getElementById('boostBtn');

            if (brakeBtn) {
                brakeBtn.onclick = () => {
                    ship.vel *= 0.5;
                    showFeedback("FREIO APLICADO");
                };
                console.log("Botão de freio configurado");
            } else {
                console.error("Botão de freio não encontrado");
            }

            if (boostBtn) {
                boostBtn.onclick = () => {
                    if (ship.boostEnergy > 20) {
                        const rad = ship.heading * Math.PI / 180;
                        ship.vel += Math.cos(rad) * ship.turboMultiplier * 100;
                        ship.boostEnergy -= 30;
                        showFeedback("TURBO ATIVADO");
                    } else {
                        showFeedback("TURBO SEM ENERGIA");
                    }
                };
                console.log("Botão de turbo configurado");
            } else {
                console.error("Botão de turbo não encontrado");
            }

            // 🔹 Capturar teclas W, A, S, D e salvar a posição
            // 🔹 Capturar teclas W, A, S, D (sem criar intervalos)
            document.addEventListener('keydown', e => {
                const step = 10;
                switch (e.key) {
                    case 'w': ship.y -= step; break;
                    case 's': ship.y += step; break;
                    case 'a': ship.x -= step; break;
                    case 'd': ship.x += step; break;
                }

                // Atualiza o HUD visualmente
                hudPos.textContent = `${ship.x.toFixed(0)},${ship.y.toFixed(0)}`;
            });

        }

        // Configurar painel de configurações
        function setupSettings() {
            const mouseSensitivity = document.getElementById('mouseSensitivity');
            const sfxVolume = document.getElementById('sfxVolume');
            const resetSettings = document.getElementById('resetSettings');
            const saveSettings = document.getElementById('saveSettings');

            if (mouseSensitivity) {
                mouseSensitivity.addEventListener('input', (e) => {
                    settings.mouseSensitivity = parseFloat(e.target.value);
                });
            }

            if (sfxVolume) {
                sfxVolume.addEventListener('input', (e) => {
                    settings.sfxVolume = parseFloat(e.target.value);
                });
            }

            // Configurar toggles
            const toggles = ['invertY', 'soundEnabled', 'showHologram', 'showScanner', 'showWaypoints', 'showPlanetView'];
            toggles.forEach(toggleId => {
                const toggle = document.getElementById(toggleId);
                if (toggle) {
                    toggle.addEventListener('click', () => {
                        const isActive = toggle.classList.contains('active');
                        toggle.classList.toggle('active');
                        settings[toggleId] = !isActive;

                        // Aplicar configurações visuais
                        if (toggleId === 'showHologram') {
                            const hologramContainer = document.querySelector('.hologram-container');
                            if (hologramContainer) hologramContainer.style.display = settings.showHologram ? 'block' : 'none';
                        } else if (toggleId === 'showScanner') {
                            const scannerContainer = document.querySelector('.scanner-container');
                            if (scannerContainer) scannerContainer.style.display = settings.showScanner ? 'block' : 'none';
                        } else if (toggleId === 'showWaypoints') {
                            const waypointContainer = document.querySelector('.waypoint-container');
                            if (waypointContainer) waypointContainer.style.display = settings.showWaypoints ? 'block' : 'none';
                        } else if (toggleId === 'showPlanetView') {
                            const planetView = document.querySelector('.planet-view');
                            if (planetView) planetView.style.display = settings.showPlanetView ? 'block' : 'none';
                        }
                    });
                }
            });

            if (resetSettings) {
                resetSettings.addEventListener('click', () => {
                    // Redefinir configurações
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
                    if (mouseSensitivity) mouseSensitivity.value = settings.mouseSensitivity;
                    if (sfxVolume) sfxVolume.value = settings.sfxVolume;

                    // Atualizar toggles
                    toggles.forEach(toggleId => {
                        const toggle = document.getElementById(toggleId);
                        if (toggle) {
                            if (settings[toggleId]) {
                                toggle.classList.add('active');
                            } else {
                                toggle.classList.remove('active');
                            }
                        }
                    });

                    // Aplicar configurações visuais
                    const hologramContainer = document.querySelector('.hologram-container');
                    if (hologramContainer) hologramContainer.style.display = settings.showHologram ? 'block' : 'none';

                    const scannerContainer = document.querySelector('.scanner-container');
                    if (scannerContainer) scannerContainer.style.display = settings.showScanner ? 'block' : 'none';

                    const waypointContainer = document.querySelector('.waypoint-container');
                    if (waypointContainer) waypointContainer.style.display = settings.showWaypoints ? 'block' : 'none';

                    const planetView = document.querySelector('.planet-view');
                    if (planetView) planetView.style.display = settings.showPlanetView ? 'block' : 'none';

                    showFeedback('Configurações redefinidas');
                });
            }

            if (saveSettings) {
                saveSettings.addEventListener('click', () => {
                    localStorage.setItem('spaceGameSettings', JSON.stringify(settings));
                    showFeedback('Configurações salvas');
                });
            }
        }

        // Criar painel de navegação por waypoints
        function createWaypointNav() {
            const navContainer = document.getElementById('waypointNav');
            if (!navContainer) {
                console.error("Container de waypoints não encontrado");
                return;
            }

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

                item.addEventListener('click', () => {
                    selectWaypoint(index);
                });

                navContainer.appendChild(item);
            });

            console.log("Painel de waypoints criado");
        }

        // Selecionar waypoint
        function selectWaypoint(index) {
            if (index >= 0 && index < waypoints.length) {
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

                showFeedback(`Waypoint selecionado: ${targetWaypoint.name}`);
            }
        }

        // Atualizar painel de waypoints
        function updateWaypointNav() {
            const navItems = document.querySelectorAll('.waypoint-item');

            waypoints.forEach((waypoint, index) => {
                if (navItems[index]) {
                    const dx = waypoint.x - ship.x;
                    const dy = waypoint.y - ship.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    const distDiv = navItems[index].querySelector('.waypoint-distance');
                    if (distDiv) distDiv.textContent = dist.toFixed(0) + ' u';

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
            if (!scanner) {
                console.error("Scanner não encontrado");
                return;
            }

            // Remover objetos antigos
            const oldObjects = scanner.querySelectorAll('.scanner-object');
            oldObjects.forEach(obj => obj.remove());

            // Adicionar planetas próximos
            const maxObjects = 6;
            let objectsAdded = 0;

            if (window.planets && window.planets.bodies) {
                for (const body of window.planets.bodies) {
                    if (objectsAdded >= maxObjects) break;

                    const dx = body.x - ship.x;
                    const dy = body.y - ship.y;
                    const distance = Math.hypot(dx, dy);

                    if (distance < 30000) {
                        const angle = Math.atan2(dy, dx);

                        const objElement = document.createElement('div');
                        objElement.className = 'scanner-object';

                        const radius = 35;
                        const centerX = 50;
                        const centerY = 50;

                        const x = centerX + radius * Math.cos(angle);
                        const y = centerY + radius * Math.sin(angle);

                        objElement.style.left = `${x}px`;
                        objElement.style.top = `${y}px`;
                        objElement.style.transform = 'translate(-50%, -50%)';

                        if (body.name === "Sol") {
                            objElement.style.background = 'rgba(255,200,0,0.9)';
                        } else {
                            objElement.style.background = 'rgba(0,255,255,0.9)';
                        }

                        const size = Math.max(4, 10 - (distance / 30000) * 6);
                        objElement.style.width = `${size}px`;
                        objElement.style.height = `${size}px`;

                        objElement.title = `${body.name} - ${distance.toFixed(0)}u`;

                        scanner.appendChild(objElement);
                        objectsAdded++;
                    }
                }
            }

            // Adicionar waypoints próximos
            for (const waypoint of waypoints) {
                if (objectsAdded >= maxObjects) break;

                const dx = waypoint.x - ship.x;
                const dy = waypoint.y - ship.y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 30000) {
                    const angle = Math.atan2(dy, dx);

                    const objElement = document.createElement('div');
                    objElement.className = 'scanner-object';

                    const radius = 35;
                    const centerX = 50;
                    const centerY = 50;

                    const x = centerX + radius * Math.cos(angle);
                    const y = centerY + radius * Math.sin(angle);

                    objElement.style.left = `${x}px`;
                    objElement.style.top = `${y}px`;
                    objElement.style.transform = 'translate(-50%, -50%)';

                    objElement.style.background = 'rgba(247,37,133,0.9)';

                    const size = Math.max(4, 8 - (distance / 30000) * 4);
                    objElement.style.width = `${size}px`;
                    objElement.style.height = `${size}px`;

                    objElement.title = `${waypoint.name} - ${distance.toFixed(0)}u`;

                    scanner.appendChild(objElement);
                    objectsAdded++;
                }
            }
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

        // Inicialização
        setupControls();
        setupSettings();
        createWaypointNav();

        // Exportar funções para uso global
        window.shipPanel = {
            updateHUD,
            updateWaypointNav,
            updateScannerObjects
        };

        let salvando = false;
        setInterval(() => {
            if (!salvando && typeof ship !== 'undefined') {
                salvando = true;
                salvarPosicao(ship.x, ship.y);
                setTimeout(() => salvando = false, 9500); // evita sobreposição
            }
        }, 10000);


        // 🎙️ Controle do painel de microfone
        document.addEventListener("DOMContentLoaded", () => {
            const micPanel = document.getElementById("micPanel");
            const micBtn = document.getElementById("micToggleBtn");
            const micIndicator = document.getElementById("micIndicator");

            if (!micPanel) {
                console.warn("⚠️ micPanel não encontrado no DOM");
                return;
            }

            // começa oculto
            micPanel.classList.add("hidden");
            let micAtivo = false;

            // alternar microfone ligado/desligado
            function alternarMicrofone() {
                micAtivo = !micAtivo;
                micIndicator.classList.toggle("active", micAtivo);
                micBtn.textContent = micAtivo ? "Desativar" : "Ativar";
                showFeedback(micAtivo ? "🎤 Microfone ligado" : "🔇 Microfone desligado");

                // 🔹 Inicializa o chat de voz multiplayer quando ativado
                if (micAtivo && typeof initVoiceChat === "function") {
                    console.log("🎧 Iniciando chat de voz para jogador", PLAYER_ID);
                    initVoiceChat(PLAYER_ID);
                }

                // 🔹 Se desligar, apenas informa (poderemos encerrar futuramente)
                if (!micAtivo) {
                    console.log("🔇 Chat de voz pausado");
                }
            }


            // alternar painel visível/oculto
            function alternarPainel() {
                micPanel.classList.toggle("hidden");
                const visivel = !micPanel.classList.contains("hidden");
                showFeedback(visivel ? "🎙️ Painel aberto" : "🔇 Painel oculto");
                console.log("Painel:", visivel ? "aberto" : "oculto");
            }

            // clique no botão → ativa microfone
            micBtn.addEventListener("click", alternarMicrofone);

            // tecla J → abre/fecha painel
            window.addEventListener("keydown", (e) => {
                if (e.key.toLowerCase() === "j") {
                    e.preventDefault();
                    e.stopPropagation();
                    alternarPainel();
                }
            });

        });



        const SHIP_SPRITES = {
            idle: 'images/naves/1c.png', // motor off
            engine: 'images/naves/1d.png', // motor on (foguinho central)
            left: 'images/naves/1b.png', // inclinada p/ esquerda
            right: 'images/naves/1a.png', // inclinada p/ direita
        };

        // cria o overlay e pré-carrega as imagens
        function createShipSpriteOverlay() {
            // container
            let el = document.getElementById('shipSprite');
            if (!el) {
                el = document.createElement('div');
                el.id = 'shipSprite';
                const img = document.createElement('img');
                img.alt = 'Ship';
                img.draggable = false;
                el.appendChild(img);
                document.body.appendChild(el);
            }

            // pré-load simples
            Object.values(SHIP_SPRITES).forEach(src => { const i = new Image(); i.src = src; });

            const img = el.querySelector('img');

            let currentSrc = '';
            let tilt = 0;        // graus
            let slide = 0;       // px
            let acceleratingPulseUntil = 0; // timestamp para "pico" visual após turbo/botão

            // estado de teclas (não conflita com seus listeners existentes)
            const keys = { w: false, a: false, d: false };

            function setImg(src) {
                if (src !== currentSrc) {
                    currentSrc = src;
                    img.src = src;
                }
            }

            function setTransform() {
                img.style.transform = `translateX(${slide}px) rotate(${tilt}deg)`;
            }

            // Determina sprite com base em teclas e boost
            function computeState() {
                const now = Date.now();

                const turningLeft = keys.a;
                const turningRight = keys.d;
                const accelerating = keys.w || (now < acceleratingPulseUntil);

                // base: engine vs idle
                let base = accelerating ? 'engine' : 'idle';

                // prioridade para direção
                if (turningLeft && !turningRight) {
                    setImg(SHIP_SPRITES.left);
                    tilt = -10;          // inclina p/ esquerda
                    slide = -10;          // deslize sutil
                } else if (turningRight && !turningLeft) {
                    setImg(SHIP_SPRITES.right);
                    tilt = 10;           // inclina p/ direita
                    slide = 10;
                } else {
                    setImg(SHIP_SPRITES[base]);
                    tilt = accelerating ? -2 : 0;   // leve nariz baixo quando acelera
                    slide = 0;
                }

                // efeito extra quando acelerando (brilho leve)
                img.style.filter = accelerating ? 'drop-shadow(0 0 6px rgba(255,160,50,0.7))' : 'none';
                setTransform();
            }

            // Ouça suas teclas sem criar intervalos
            // Controle de teclas com suporte às setas ← → (rotação de câmera)
            // ============================================================
            // 🔹 Atalhos personalizados para alternar componentes da interface
            // ============================================================

            document.addEventListener('keydown', (e) => {
                // evita interferir com digitação em campos
                if (['input', 'textarea'].includes(document.activeElement.tagName.toLowerCase())) return;

                const toggleVisibility = (selector) => {
                    const el = document.querySelector(selector);
                    if (!el) return;
                    el.style.display = (el.style.display === 'none') ? 'block' : 'none';
                };

                switch (e.key.toLowerCase()) {
                    case 'k':
                        toggleVisibility('.planet-view'); // Visão Planetária
                        showFeedback('🔭 Visão Planetária alternada');
                        break;

                    case 'l':
                        toggleVisibility('.scanner-container'); // Scanner
                        showFeedback('📡 Scanner alternado');
                        break;

                    case 'p':
                        toggleVisibility('.waypoint-container'); // Waypoints
                        showFeedback('📍 Waypoints alternados');
                        break;

                    case 'o':
                        toggleVisibility('.hologram-container'); // Mapa Holográfico
                        showFeedback('🛰️ Mapa Holográfico alternado');
                        break;
                    case 'j':
                        toggleVisibility('#micPanel'); // Painel de Comunicação (microfone)
                        showFeedback('🎙️ Painel de Comunicação alternado');
                        break;

                    case 'i':
                        // Alterna HUD + controles
                        toggleVisibility('#hud');
                        toggleVisibility('.controls');
                        showFeedback('🎛️ Painel principal alternado');
                        break;
                }
            });

            document.addEventListener('keyup', (e) => {
                if (e.key === 'w' || e.key === 'a' || e.key === 'ArrowUp') keys.w = false;

                // if (e.key === 'w' || e.key === 'ArrowUp') keys.w = false;

                if (e.key === 'ArrowLeft') keys.a = false;
                if (e.key === 'ArrowRight') keys.d = false;

                //if (e.key === 'a' || e.key === 'ArrowLeft') keys.a = false;
                // if (e.key === 'd' || e.key === 'ArrowRight') keys.d = false;

                computeState();
            });



            // Integra com seus botões já existentes (boost/freio) se existirem
            const boostBtn = document.getElementById('boostBtn');
            if (boostBtn) {
                boostBtn.addEventListener('click', () => {
                    // dá um “pico” visual de aceleração por ~700ms
                    acceleratingPulseUntil = Date.now() + 700;
                    computeState();
                });
            }

            // estado inicial
            setImg(SHIP_SPRITES.idle);
            setTransform();

            // pequena cola pública
            window.shipSprite = {
                pulseAccelerate(ms = 700) {
                    acceleratingPulseUntil = Date.now() + ms;
                    computeState();
                },
                setCenterOffset(px) { slide = px; setTransform(); },
            };

            // acompanhando o game loop existente: apenas “ouve” frames
            // (não cria intervalos: usa rAF leve para suavizar quando o jogo está ativo)
            let raf = null;
            function smoothLoop() {
                computeState();
                raf = requestAnimationFrame(smoothLoop);
            }
            raf = requestAnimationFrame(smoothLoop);

            // se quiser encerrar: cancelAnimationFrame(raf)
            return el;
        }

        // cria o overlay
        createShipSpriteOverlay();







        return true;
    }

    // Verificar se o DOM está carregado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShipPanel);
    } else {
        initShipPanel();
    }

})();
