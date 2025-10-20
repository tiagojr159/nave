// enemyShips.js - Camada de naves inimigas
(function() {
    const enemies = [
        { id: 1, name: "Pirata Espacial", x: 45000, y: 35000, type: "pirate", health: 100, speed: 300 },
        { id: 2, name: "Nave de Batalha", x: 75000, y: 65000, type: "battleship", health: 200, speed: 200 },
        { id: 3, name: "Caça Imperial", x: 25000, y: 75000, type: "fighter", health: 75, speed: 500 }
    ];
    
    function drawEnemyShips(ctx, width, height) {
        for(const enemy of enemies) {
            const dx = enemy.x - ship.x;
            const dy = enemy.y - ship.y;
            const dist = Math.hypot(dx, dy);
            
            if(dist > MAX_DRAW_DIST) continue;
            
            // Calcular posição na tela
            const angToEnemy = Math.atan2(dy, dx);
            let rel = angToEnemy - (ship.heading * Math.PI / 180);
            rel = Math.atan2(Math.sin(rel), Math.cos(rel));
            
            const screenX = (width/2) + (rel * (width/Math.PI));
            const screenY = height*0.6 + ship.pitch*3;
            
            // Tamanho aparente
            const scale = Math.min(Math.max((FOV / Math.max(60, dist)), 0.02), 3.5);
            const size = 25 * scale;
            
            // Desenhar nave inimiga
            ctx.save();
            ctx.translate(screenX, screenY);
            
            // Cor baseada no tipo
            if (enemy.type === "pirate") {
                ctx.fillStyle = '#ff5555';
            } else if (enemy.type === "battleship") {
                ctx.fillStyle = '#bd93f9';
            } else {
                ctx.fillStyle = '#ff79c6';
            }
            
            // Desenhar forma baseada no tipo
            if (enemy.type === "battleship") {
                ctx.fillRect(-size, -size/2, size*2, size);
            } else {
                ctx.beginPath();
                ctx.moveTo(0, -size);
                ctx.lineTo(-size/2, size/2);
                ctx.lineTo(size/2, size/2);
                ctx.closePath();
                ctx.fill();
            }
            
            // Nome se grande o suficiente
            if(size > 15) {
                ctx.fillStyle = '#ff5555';
                ctx.font = '10px system-ui, Arial';
                ctx.textAlign='center';
                ctx.fillText(enemy.name, 0, -size - 5);
            }
            
            ctx.restore();
        }
    }
    
    function updateEnemies(dt) {
        for(const enemy of enemies) {
            const dx = ship.x - enemy.x;
            const dy = ship.y - enemy.y;
            const dist = Math.hypot(dx, dy);
            
            // Se o jogador estiver próximo, perseguir
            if(dist < 20000) {
                const angle = Math.atan2(dy, dx);
                enemy.x += Math.cos(angle) * enemy.speed * dt;
                enemy.y += Math.sin(angle) * enemy.speed * dt;
            } else {
                // Movimento aleatório quando longe
                enemy.x += (Math.random() - 0.5) * 100 * dt;
                enemy.y += (Math.random() - 0.5) * 100 * dt;
            }
        }
    }
    
    // Exportar funções para uso global
    window.enemyShips = {
        drawEnemyShips,
        updateEnemies,
        enemies
    };
})();