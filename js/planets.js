// planets.js — usa o nome da imagem vindo direto do banco
(function () {
    window.planets = window.planets || {};
    window.cache = window.cache || {};
    const IMG_PATH = 'images/';

    let bodies = [];
    let nearestPlanet = null;
    let astrosCarregados = false;

    // 🔹 Precarrega todas as imagens que vierem do banco
    function preloadImages(imageNames) {
        const unique = [...new Set(imageNames.filter(Boolean))];
        unique.forEach(imgName => {
            if (window.cache[imgName]) return;
            const img = new Image();
            img.onload = () => { window.cache[imgName] = img; };
            img.onerror = () => console.warn('Imagem não encontrada:', IMG_PATH + imgName);
            img.src = IMG_PATH + imgName;
        });
    }

    // 🔹 Busca planetas do banco (usa nome da imagem exatamente como está no BD)
    async function loadBodies() {
        try {
            const response = await fetch("db/get_astros.php");
            const data = await response.json();

            if (!Array.isArray(data)) {
                console.error("Resposta inválida:", data);
                return;
            }

            bodies = data.map(b => ({
                ...b,
                rotation: Math.random() * Math.PI * 2,
                rotationSpeed: b.rotationSpeed || 0.002
            }));

            const imageList = bodies.map(b => b.img).filter(Boolean);
            preloadImages(imageList);

            astrosCarregados = true;
            console.log("✅ Astros carregados do banco:", bodies);
        } catch (err) {
            console.error("Erro ao carregar astros:", err);
        }
    }

    async function waitForPlanetsLoaded() {
        while (!astrosCarregados) {
            await new Promise(r => setTimeout(r, 100));
        }
        return true;
    }

    // 🔹 Desenha os planetas com a imagem que veio do banco
    function drawPlanets(ctx, width, height) {
        if (!bodies.length) return { nearestName: "—", nearestDist: Infinity };

        let nearestName = "—", nearestDist = Infinity;

        for (const b of bodies) {
            b.rotation += b.rotationSpeed || 0.002;

            const dx = b.x - ship.x;
            const dy = b.y - ship.y;
            const dist = Math.hypot(dx, dy);
            if (dist < nearestDist) { nearestDist = dist; nearestPlanet = b; nearestName = b.name; }
            if (dist > MAX_DRAW_DIST) continue;

            const angToBody = Math.atan2(dy, dx);
            let rel = angToBody - (ship.heading * Math.PI / 180);
            rel = Math.atan2(Math.sin(rel), Math.cos(rel));

            const screenX = (width / 2) + (rel * (width / Math.PI));
            const screenY = height * 0.6 + ship.pitch * 3;
            const scale = Math.min(Math.max((FOV / Math.max(60, dist)), 0.02), 3.5);
            const rad = b.r * scale;

            ctx.save();
            ctx.translate(screenX, screenY);
            ctx.rotate(b.rotation);

            const im = b.img && window.cache[b.img] ? window.cache[b.img] : null;
            if (im) {
                ctx.drawImage(im, -rad, -rad, rad * 2, rad * 2);
            } else {
                ctx.fillStyle = b.cor || "#888";
                ctx.beginPath();
                ctx.arc(0, 0, rad, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.restore();

            if (rad > 24) {
                ctx.fillStyle = "#8aa2ff";
                ctx.font = "12px system-ui, Arial";
                ctx.textAlign = "center";
                ctx.fillText(b.name, screenX, screenY - rad - 8);
            }
        }

        return { nearestName, nearestDist };
    }

    // 🔹 Atualiza painel lateral da nave
    function updatePlanetView() {
        if (!nearestPlanet) return;

        const dx = nearestPlanet.x - ship.x;
        const dy = nearestPlanet.y - ship.y;
        const distance = Math.hypot(dx, dy);

        document.getElementById('planetName').textContent = nearestPlanet.name;
        document.getElementById('planetDistance').textContent = distance.toFixed(0) + " u";
        document.getElementById('planetDiameter').textContent = (nearestPlanet.r * 2).toFixed(0) + " km";
        document.getElementById('planetTemp').textContent = nearestPlanet.temp || "Desconhecida";
        document.getElementById('planetAtmosphere').textContent = nearestPlanet.atmosphere || "Desconhecida";

        if (nearestPlanet.img && window.cache[nearestPlanet.img]) {
            document.getElementById('planetImage').src = IMG_PATH + nearestPlanet.img;
        }

        const proximityPercent = Math.min(100, (distance / 10000) * 100);
        const bar = document.getElementById('planetProximityBar');
        bar.style.width = proximityPercent + "%";
        bar.style.background =
            proximityPercent < 20 ? "linear-gradient(90deg, var(--danger), var(--ok))" :
            proximityPercent < 50 ? "linear-gradient(90deg, #ff9500, var(--ok))" :
            "linear-gradient(90deg, var(--ok), #00a8ff)";
    }

    // 🔹 Inicia o carregamento
    loadBodies();

    // 🔹 Exporta para uso no main.js
    window.planets = {
        waitForPlanetsLoaded,
        drawPlanets,
        updatePlanetView,
        get bodies() { return bodies; }
    };
})();
