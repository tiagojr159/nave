// main.js - Arquivo principal que inicializa o jogo
document.addEventListener('DOMContentLoaded', async () => {
    console.log("DOM carregado, inicializando jogo");

    // Inicializar canvas
    const view = document.getElementById('view');
    const ctx = view.getContext('2d');
    const mapCanvas = document.getElementById('map');
    const mapCtx = mapCanvas.getContext('2d');
    const hologramCanvas = document.getElementById('hologram');
    const hologramCtx = hologramCanvas.getContext('2d');

    let W = 0, H = 0, lastT = 0;

    // 🔹 Sprites da nave do jogador
    const shipSprites = {
        idle: new Image(),
        engine: new Image(),
        left: new Image(),
        right: new Image()
    };
    shipSprites.idle.src = 'images/naves/1c.png';   // parada
    shipSprites.engine.src = 'images/naves/1d.png'; // motor ligado
    shipSprites.left.src = 'images/naves/1b.png';   // inclinando esquerda
    shipSprites.right.src = 'images/naves/1a.png';  // inclinando direita



    // Funções auxiliares
    function clamp(v, a, b) {
        return Math.max(a, Math.min(b, v));
    }

    function resize() {
        view.width = W = view.clientWidth;
        view.height = H = view.clientHeight;
        mapCanvas.width = mapCanvas.clientWidth;
        mapCanvas.height = mapCanvas.clientHeight - 20;
        hologramCanvas.width = window.innerWidth > 900 ? 196 : 156;
        hologramCanvas.height = window.innerWidth > 900 ? 120 : 90;
        console.log("Canvas redimensionado para", W, "x", H);
    }

    function flash(msg) {
        const toast = document.getElementById('toast');
        if (toast) {
            toast.textContent = msg;
            toast.style.display = 'block';
            clearTimeout(flash._t);
            flash._t = setTimeout(() => toast.style.display = 'none', 1500);
        }
    }

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
                const toggles = ['invertY', 'soundEnabled', 'showHologram', 'showScanner', 'showWaypoints', 'showPlanetView'];
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
                document.querySelector('.hologram-container').style.display = settings.showHologram ? 'block' : 'none';
                document.querySelector('.scanner-container').style.display = settings.showScanner ? 'block' : 'none';
                document.querySelector('.waypoint-container').style.display = settings.showWaypoints ? 'block' : 'none';
                document.querySelector('.planet-view').style.display = settings.showPlanetView ? 'block' : 'none';

                console.log("Configurações carregadas com sucesso");
            } catch (e) {
                console.error('Erro ao carregar configurações:', e);
            }
        }
    }

    // Carregar imagens
    async function loadImages() {
        console.log("Carregando imagens dos planetas");

        await Promise.all(planets.bodies.map(async b => {
            const im = new Image();
            im.src = IMG_PATH + b.img;
            await new Promise(resolve => {
                im.onload = () => {
                    cache[b.img] = im;
                    console.log(`Imagem ${b.img} carregada`);
                    resolve();
                };
                im.onerror = () => {
                    console.warn("Falha ao carregar", b.img);
                    resolve();
                };
            });
        }));

        console.log("Todas as imagens foram carregadas");
    }

    // Configurar controles
    function setupControls() {
        console.log("Configurando controles");

        window.addEventListener('keydown', e => {
            keys[e.key.toLowerCase()] = true;
            if (["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown"].includes(e.key)) e.preventDefault();
            if (e.key.toLowerCase() === 'm') {
                showMap = !showMap;
                document.getElementById('minimap').style.display = showMap ? 'block' : 'none';
            }
            if (e.key.toLowerCase() === 'r') {
                ship.x = 5500; ship.y = 55500; ship.heading = 0; ship.vel = 0;
                flash("POSIÇÃO RESETADA");
            }
            if (e.key.toLowerCase() === 'escape') {
                document.getElementById('settingsPanel').classList.toggle('active');
            }
        });

        window.addEventListener('keyup', e => {
            keys[e.key.toLowerCase()] = false;
        });

        window.addEventListener('resize', resize);

        console.log("Controles configurados com sucesso");
    }

    // Atualizar estado do jogo
    function update(dt) {
        // Rotação por setas
        if (keys['arrowleft']) ship.heading -= ship.turnRate * dt;
        if (keys['arrowright']) ship.heading += ship.turnRate * dt;
        // Inclinação "visual"
        if (keys['arrowup']) ship.pitch = clamp(ship.pitch - 40 * dt, -20, 20);
        if (keys['arrowdown']) ship.pitch = clamp(ship.pitch + 40 * dt, -20, 20);
        ship.pitch *= (1 - 0.08); // amortecimento

        // Aceleração: A acelera; B freia/inverte
        if (keys['a']) ship.acc = 1400;
        else if (keys['b']) ship.acc = -1000;
        else ship.acc = 0;

        // Integra velocidade
        ship.vel += ship.acc * dt;
        ship.vel = clamp(ship.vel, -ship.maxSpeed * 0.5, ship.maxSpeed);
        // Atrito suave
        ship.vel *= (1 - 0.15 * dt);

        // Movimento no plano seguindo heading
        const rad = ship.heading * Math.PI / 180;
        ship.x += Math.cos(rad) * ship.vel * dt;
        ship.y += Math.sin(rad) * ship.vel * dt;

        // Limites do mundo
        ship.x = clamp(ship.x, 0, WORLD_W);
        ship.y = clamp(ship.y, 0, WORLD_H);

        // Gerenciar energia do turbo
        if (keys['shift'] && ship.boostEnergy > 0) {
            ship.boostEnergy -= ship.boostConsumptionRate;
        } else {
            ship.boostEnergy = Math.min(100, ship.boostEnergy + ship.boostRechargeRate);
        }

        // Atualizar outros elementos
        if (window.otherShips) otherShips.updateNPCs(dt);
        if (window.enemyShips) enemyShips.updateEnemies(dt);





    }

    // Renderizar o jogo
    function draw() {
        // Desenhar fundo
        if (window.universeBackground) universeBackground.drawBackground(ctx, W, H);

        // Desenhar planetas
        let nearestInfo = { nearestName: "—", nearestDist: Infinity };
        if (window.planets) {
            nearestInfo = planets.drawPlanets(ctx, W, H);
        }

        // Desenhar outras naves
        if (window.otherShips) otherShips.drawOtherShips(ctx, W, H);

        // Desenhar naves inimigas
        if (window.enemyShips) enemyShips.drawEnemyShips(ctx, W, H);

        // Atualizar HUD
        if (window.shipPanel) {
            shipPanel.updateHUD(nearestInfo);
        } else {
            console.error("shipPanel não está disponível");
        }

        // Atualizar outros elementos da UI
        if (window.shipPanel) {
            shipPanel.updateWaypointNav();
            shipPanel.updateScannerObjects();
        }

        if (window.planets) planets.updatePlanetView();
        if (window.universeBackground) universeBackground.updateSpeedEffect();

        // 🔹 Desenhar a nave do jogador
        if (window.ship) {
            ctx.save();
            ctx.translate(W / 2, H * 0.6); // centro da tela = posição da nave
            ctx.rotate(ship.heading * Math.PI / 180);

            // Triângulo representando a nave
            ctx.beginPath();
            ctx.moveTo(0, -20);
            ctx.lineTo(10, 10);
            ctx.lineTo(-10, 10);
            ctx.closePath();


            ctx.restore();
        }




        // "Retícula" simples
        ctx.strokeStyle = '#5865f299';
        ctx.beginPath();
        ctx.arc(W / 2, H * 0.6, 20, 0, Math.PI * 2);
        ctx.moveTo(W / 2 - 28, H * 0.6); ctx.lineTo(W / 2 + 28, H * 0.6);
        ctx.stroke();
    }

    // Desenhar minimapa
    function drawMiniMap() {
        if (!showMap) return;
        const mW = mapCanvas.width, mH = mapCanvas.height;
        mapCtx.clearRect(0, 0, mW, mH);
        // moldura
        mapCtx.fillStyle = '#0b1220';
        mapCtx.fillRect(0, 0, mW, mH);
        // escala mundo → mapa
        const sx = mW / WORLD_W;
        const sy = mH / WORLD_H;
        // planetas
        if (window.planets) {
            for (const b of planets.bodies) {
                const x = b.x * sx;
                const y = b.y * sy;
                mapCtx.fillStyle = '#3e50ff';
                mapCtx.beginPath();
                mapCtx.arc(x, y, Math.max(1.5, b.r * sx * 0.4), 0, Math.PI * 2);
                mapCtx.fill();
            }
        }
        // waypoints
        for (const waypoint of waypoints) {
            const x = waypoint.x * sx;
            const y = waypoint.y * sy;
            mapCtx.fillStyle = targetWaypoint === waypoint ? '#ff5a5a' : '#f72585';
            mapCtx.beginPath();
            mapCtx.arc(x, y, 3, 0, Math.PI * 2);
            mapCtx.fill();
        }
        // outras naves
        if (window.otherShips) {
            for (const npc of otherShips.npcs) {
                const x = npc.x * sx;
                const y = npc.y * sy;
                mapCtx.fillStyle = npc.friendly ? '#50fa7b' : '#ff5555';
                mapCtx.fillRect(x - 2, y - 2, 4, 4);
            }
        }
        // naves inimigas
        if (window.enemyShips) {
            for (const enemy of enemyShips.enemies) {
                const x = enemy.x * sx;
                const y = enemy.y * sy;
                mapCtx.fillStyle = '#ff5555';
                mapCtx.beginPath();
                mapCtx.arc(x, y, 3, 0, Math.PI * 2);
                mapCtx.fill();
            }
        }
        // nave
        const nx = ship.x * sx, ny = ship.y * sy;
        mapCtx.fillStyle = '#ff5a5a';
        mapCtx.beginPath();
        mapCtx.arc(nx, ny, 3.5, 0, Math.PI * 2);
        mapCtx.fill();
        // rumo
        mapCtx.strokeStyle = '#ff8a8a';
        mapCtx.beginPath();
        mapCtx.moveTo(nx, ny);
        const r = ship.heading * Math.PI / 180;
        mapCtx.lineTo(nx + Math.cos(r) * 18, ny + Math.sin(r) * 18);
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

    // Desenhar holograma
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
        c.strokeRect(2, 2, width - 4, height - 4);

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

        if (window.planets) {
            for (const body of planets.bodies) {
                const x = centerX + (body.x - WORLD_W / 2) * scale;
                const y = centerY + (body.y - WORLD_H / 2) * scale;

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
        }

        // Desenhar waypoints
        for (const waypoint of waypoints) {
            const x = centerX + (waypoint.x - WORLD_W / 2) * scale;
            const y = centerY + (waypoint.y - WORLD_H / 2) * scale;

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
                const shipX = centerX + (ship.x - WORLD_W / 2) * scale;
                const shipY = centerY + (ship.y - WORLD_H / 2) * scale;

                c.beginPath();
                c.moveTo(shipX, shipY);
                c.lineTo(x, y);
                c.stroke();
            }
        }

        // Desenhar posição da nave
        const shipX = centerX + (ship.x - WORLD_W / 2) * scale;
        const shipY = centerY + (ship.y - WORLD_H / 2) * scale;

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
            const waypointX = centerX + (targetWaypoint.x - WORLD_W / 2) * scale;
            const waypointY = centerY + (targetWaypoint.y - WORLD_H / 2) * scale;

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

    // Loop do jogo
    function loop(t) {
        const dt = Math.min(0.05, (t - lastT) / 1000);
        lastT = t;

        update(dt);
        draw();
        drawMiniMap();
        drawHologram();

        requestAnimationFrame(loop);
    }

    // Inicialização
    console.log("Iniciando inicialização do jogo");

    resize();
    loadSettings();
    setupControls();
    await loadImages();

    // ======================================================
    // 🔹 Inicializar objeto da nave local se ainda não existir
    // ======================================================
    if (typeof window.ship === "undefined" || !window.ship) {
        window.ship = {
            id: typeof PLAYER_ID !== "undefined" ? PLAYER_ID : 0,
            nome: "Nave Jogador",
            x: 50000 + Math.random() * 5000,
            y: 50000 + Math.random() * 5000,
            heading: 0,
            vel: 0,
            acc: 0,
            pitch: 0,
            maxSpeed: 800,
            turnRate: 90,
            boostEnergy: 100,
            boostConsumptionRate: 0.5,
            boostRechargeRate: 0.25,
            turboMultiplier: 2
        };
        console.log("✅ Nave inicializada:", window.ship);
    } else {
        console.log("🛰️ Nave já definida:", window.ship);
    }








    // Verificar se todos os módulos foram carregados
    console.log("Verificando módulos:");
    console.log("- universeBackground:", window.universeBackground ? "OK" : "FALHA");
    console.log("- planets:", window.planets ? "OK" : "FALHA");
    console.log("- otherShips:", window.otherShips ? "OK" : "FALHA");
    console.log("- enemyShips:", window.enemyShips ? "OK" : "FALHA");
    console.log("- shipPanel:", window.shipPanel ? "OK" : "FALHA");

    flash("Bem-vindo! Acelere com A, freie com B. Gire com ← →. Setor 100k x 100k liberado. 🚀");
    lastT = performance.now();
    requestAnimationFrame(loop);
    if (typeof initialPosition !== 'undefined') {
        if (typeof ship !== 'undefined') {
            ship.x = initialPosition.x || 100;
            ship.y = initialPosition.y || 100;
            console.log(`🚀 Nave iniciada em posição salva: X=${ship.x}, Y=${ship.y}`);
        } else {
            console.warn("⚠️ ship ainda não definido na inicialização.");
        }
    } else {
        console.warn("⚠️ initialPosition não definido. Iniciando nave na posição padrão (100,100).");
    }

    console.log("Jogo inicializado com sucesso");
    // 🔹 Salvamento automático da posição (fora do loop principal)
    setInterval(() => {
        if (window.ship && typeof salvarPosicao === 'function') {
            salvarPosicao(ship.x, ship.y);
        }
    }, 2000);
// ===========================================================
    // 🎙️ MICROFONE PANEL - MOSTRAR/OCULTAR E ATIVAR/DESATIVAR
    // ===========================================================
    (function () {
        const micPanel = document.getElementById("micPanel");
        const micIndicator = document.getElementById("micIndicator");
        const micBtn = document.getElementById("micToggleBtn");

        if (!micPanel || !micIndicator || !micBtn) {
            console.warn("⚠️ Painel de microfone não encontrado no DOM.");
            return;
        }

        console.log("✅ Painel de microfone detectado e funcional");

        let micAtivo = false;
        let micVisivel = false;

        function alternarMicrofone() {
            micAtivo = !micAtivo;
            micIndicator.classList.toggle("active", micAtivo);
            micBtn.textContent = micAtivo ? "Desativar" : "Ativar";
            flash(micAtivo ? "🎤 Microfone ligado" : "🔇 Microfone desligado");
        }

        function alternarPainel() {
            micVisivel = !micVisivel;
            micPanel.style.display = micVisivel ? "flex" : "none";
            console.log("🎙️ Painel de microfone:", micVisivel ? "visível" : "oculto");
            flash(micVisivel ? "🎙️ Painel de microfone aberto" : "🎙️ Painel oculto");
        }

        // 🔹 Clique no botão ativa/desativa o microfone
        micBtn.addEventListener("click", (e) => {
            e.stopPropagation(); // impede conflito com outros cliques
            alternarMicrofone();
        });

        // 🔹 Captura tecla J para mostrar/ocultar o painel
        window.addEventListener("keydown", (e) => {
            if (e.key && e.key.toLowerCase() === "j") {
                e.preventDefault();
                alternarPainel();
            }
        });
    })();
});