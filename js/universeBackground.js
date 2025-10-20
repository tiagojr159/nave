// universeBackground.js - Camada do fundo do universo
(function() {
    let stars = [];
    
    function makeStars() {
        stars = [];
        for(let i = 0; i < STAR_COUNT; i++) {
            stars.push({ 
                x: Math.random() * WORLD_W, 
                y: (Math.random() - 0.5) * WORLD_H * 2, 
                r: Math.random() * 1.6 + 0.4, 
                a: Math.random() * 0.6 + 0.2 
            });
        }
    }
    
    function drawBackground(ctx, width, height) {
        // Fundo preto
        ctx.fillStyle = '#000';
        ctx.fillRect(0, 0, width, height);
        
        // Estrelas com paralaxe
        const starOffsetX = (ship.heading % 360) / 360 * 2000;
        ctx.save();
        ctx.translate(-(starOffsetX % 2000), height / 2 + ship.pitch * 2);
        
        for(const s of stars) {
            const sx = (s.x % 2000);
            const sy = (s.y % 4000);
            ctx.globalAlpha = s.a;
            ctx.fillStyle = '#9ecbff';
            ctx.beginPath();
            ctx.arc(sx, sy, s.r, 0, Math.PI * 2);
            ctx.fill();
        }
        
        ctx.restore();
        ctx.globalAlpha = 1;
    }
    
    function updateSpeedEffect() {
        const speedEffect = document.getElementById('speedEffect');
        if (speedEffect) {
            const opacity = Math.min(0.7, Math.abs(ship.vel) / ship.maxSpeed * 0.7);
            speedEffect.style.opacity = opacity;
        }
    }
    
    // Inicialização
    makeStars();
    
    // Exportar funções para uso global
    window.universeBackground = {
        drawBackground,
        updateSpeedEffect
    };
})();