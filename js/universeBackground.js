// universeBackground.js - Fundo do universo com nebulosas, galáxias e estrelas
(function() {
    let stars = [];

    // Lista de camadas com imagens e propriedades visuais
    const layers = [
        { src: "images/universo.jpg", speed: 0.2, opacity: 0.9 },
        { src: "images/nbulosa1.png", speed: 0.4, opacity: 0.6 },
        { src: "images/nebulosa2.png", speed: 0.7, opacity: 0.5 },
        { src: "images/nebulosa3.png", speed: 1.0, opacity: 0.4 }
    ];

    // Cache de imagens
    const images = [];
    for (const layer of layers) {
        const img = new Image();
        img.src = layer.src;
        img._loaded = false;
        img._failed = false;
        img.onload = () => {
            img._loaded = true;
            console.log("✅ Fundo carregado:", layer.src);
        };
        img.onerror = () => {
            img._failed = true;
            console.warn("⚠️ Erro ao carregar:", layer.src);
        };
        images.push(img);
    }

    // Gera estrelas aleatórias
    function makeStars() {
        stars = [];
        for (let i = 0; i < STAR_COUNT; i++) {
            stars.push({
                x: Math.random() * WORLD_W,
                y: (Math.random() - 0.5) * WORLD_H * 2,
                r: Math.random() * 1.6 + 0.4,
                a: Math.random() * 0.6 + 0.2
            });
        }
    }

    // Função principal de renderização
    function drawBackground(ctx, width, height) {
        // Fundo preto
        ctx.fillStyle = "#000";
        ctx.fillRect(0, 0, width, height);

        // --- Camadas de fundo com nebulosas e galáxias ---
        for (let i = 0; i < layers.length; i++) {
            const layer = layers[i];
            const img = images[i];
            if (!img || img._failed || !img._loaded || !img.complete || img.naturalWidth === 0) continue;

            try {
                const scale = Math.max(width / img.width, height / img.height);
                const drawW = img.width * scale;
                const drawH = img.height * scale;
                const offsetX = (width - drawW) / 2;
                const offsetY = (height - drawH) / 2;

                // 🔁 Paralaxe com looping suave (reinicia a cada 360°)
                const angle = (ship.heading % 360 + 360) % 360; // normaliza ângulo entre 0–360
                const parallaxRange = 200 * layer.speed;
                const parallaxX = (angle / 360) * parallaxRange;
                const parallaxY = ship.pitch * (4 * layer.speed); // movimento vertical leve

                ctx.save();
                ctx.globalAlpha = layer.opacity;

                // movimento contínuo e looping
                ctx.translate(parallaxX % parallaxRange, parallaxY % 100);
                ctx.drawImage(img, offsetX, offsetY, drawW, drawH);

                ctx.restore();
            } catch (err) {
                console.warn(`Erro ao desenhar camada ${layer.src}:`, err.message);
            }
        }

        // --- Estrelas sobrepostas ---
        const starOffsetX = (ship.heading % 360) / 360 * 2000;
        ctx.save();
        ctx.translate(-(starOffsetX % 2000), height / 2 + ship.pitch * 2);

        for (const s of stars) {
            const sx = (s.x % 2000);
            const sy = (s.y % 4000);
            ctx.globalAlpha = s.a;
            ctx.fillStyle = "#9ecbff";
            ctx.beginPath();
            ctx.arc(sx, sy, s.r, 0, Math.PI * 2);
            ctx.fill();
        }

        ctx.restore();
        ctx.globalAlpha = 1;
    }

    // Efeito visual de velocidade (glow nos cantos)
    function updateSpeedEffect() {
        const speedEffect = document.getElementById("speedEffect");
        if (speedEffect) {
            const opacity = Math.min(0.7, Math.abs(ship.vel) / ship.maxSpeed * 0.7);
            speedEffect.style.opacity = opacity;
        }
    }

    // Inicialização
    makeStars();

    // Exportar funções globais
    window.universeBackground = {
        drawBackground,
        updateSpeedEffect
    };
})();
