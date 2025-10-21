// planets.js - Camada dos planetas
(function() {
    const bodies = [
        {name:"Sol", img:"sol.png", x:0, y:0, r:1200, temp:"5.778 K", atmosphere:"Hélio, Hidrogênio", rotation: 0, rotationSpeed: 0.0},
        {name:"Mercúrio", img:"eros.png", x:0, y:0, r:120, temp:"440 K", atmosphere:"Exosfera", rotation: 0, rotationSpeed: 0.0},
        {name:"Vênus", img:"venus.png", x:0, y:0, r:350, temp:"735 K", atmosphere:"Dióxido de Carbono", rotation: 0, rotationSpeed: 0.0},
        {name:"Terra", img:"terra.png", x:0, y:0, r:360, temp:"288 K", atmosphere:"Nitrogênio, Oxigênio", rotation: 0, rotationSpeed: 0.0},
        {name:"Marte", img:"marte.png", x:0, y:0, r:270, temp:"210 K", atmosphere:"Dióxido de Carbono", rotation: 0, rotationSpeed: 0.0},
        {name:"Ceres", img:"ceres.png", x:0, y:0, r:120, temp:"200 K", atmosphere:"Sem atmosfera", rotation: 0, rotationSpeed: 0.007},
        {name:"Júpiter", img:"jupter.png", x:0, y:0, r:900, temp:"165 K", atmosphere:"Hidrogênio, Hélio", rotation: 0, rotationSpeed: 0.0},
        {name:"Ganimedes", img:"ganimedes.png", x:0, y:0, r:260, temp:"110 K", atmosphere:"Oxigênio", rotation: 0, rotationSpeed: 0.0},
        {name:"Saturno", img:"sartuno.png", x:0, y:0, r:800, temp:"134 K", atmosphere:"Hidrogênio, Hélio", rotation: 0, rotationSpeed: 0.0},
        {name:"Asteróide", img:"asteroide.png", x:0, y:0, r:80, temp:"173 K", atmosphere:"Sem atmosfera", rotation: 0, rotationSpeed: 0.0},
        {name:"Netuno", img:"netuno.png", x:0, y:0, r:520, temp:"72 K", atmosphere:"Hidrogênio, Hélio", rotation: 0, rotationSpeed: 0.0},
    ];
    
    function randomizeBodies() {
        const placed = [];
        const minGap = 1400;
        const minFromShip = 5000;

        function ok(x,y,r){
            if(x < r || y < r || x > WORLD_W - r || y > WORLD_H - r) return false;
            const ds = Math.hypot(x - ship.x, y - ship.y);
            if(ds < Math.max(minFromShip, r*3)) return false;
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
            if(!found){
                const x = WORLD_W*0.5 + (Math.random()-0.5)*WORLD_W*0.8;
                const y = WORLD_H*0.5 + (Math.random()-0.5)*WORLD_H*0.8;
                b.x = Math.min(WORLD_W-b.r, Math.max(b.r, x));
                b.y = Math.min(WORLD_H-b.r, Math.max(b.r, y));
                placed.push({x:b.x, y:b.y, r:b.r});
            }
            
            // Inicializar rotação aleatória para cada planeta
            b.rotation = Math.random() * Math.PI * 2;
        }
    }
    
    function drawPlanets(ctx, width, height) {
        let nearestName = "—", nearestDist = Infinity;

        for(const b of bodies){
            // Atualizar a rotação do planeta
            b.rotation += b.rotationSpeed;
            
            const dx = b.x - ship.x;
            const dy = b.y - ship.y;
            const dist = Math.hypot(dx, dy);

            if(dist < nearestDist){ 
                nearestDist = dist; 
                nearestName = b.name; 
                nearestPlanet = b;
            }

            if(dist > MAX_DRAW_DIST) continue;

            const angToBody = Math.atan2(dy, dx);
            let rel = angToBody - (ship.heading * Math.PI / 180);
            rel = Math.atan2(Math.sin(rel), Math.cos(rel));

            const screenX = (width/2) + (rel * (width/Math.PI));
            const screenY = height*0.6 + ship.pitch*3;

            const scale = Math.min(Math.max((FOV / Math.max(60, dist)), 0.02), 3.5);
            const rad = b.r * scale;

            const im = cache[b.img];
            if(im){
                ctx.save();
                ctx.translate(screenX, screenY);
                
                // Aplicar rotação ao planeta
                ctx.rotate(b.rotation);
                
                const d = rad*2;
                ctx.drawImage(im, -d/2, -d/2, d, d);
                ctx.restore();
            } else {
                ctx.save();
                ctx.translate(screenX, screenY);
                
                // Aplicar rotação mesmo para o círculo de fallback
                ctx.rotate(b.rotation);
                
                ctx.fillStyle = '#3f46d9';
                ctx.beginPath(); 
                ctx.arc(0,0, rad, 0, Math.PI*2); 
                ctx.fill();
                ctx.restore();
            }

            if(rad > 24){
                ctx.fillStyle = '#8aa2ff';
                ctx.font = '12px system-ui, Arial';
                ctx.textAlign='center';
                ctx.fillText(b.name, screenX, screenY - rad - 8);
            }
        }

        return { nearestName, nearestDist };
    }
    
    function updatePlanetView() {
        if (!nearestPlanet) return;
        
        // Atualizar informações do planeta
        document.getElementById('planetName').textContent = nearestPlanet.name;
        
        const dx = nearestPlanet.x - ship.x;
        const dy = nearestPlanet.y - ship.y;
        const distance = Math.hypot(dx, dy);
        
        document.getElementById('planetDistance').textContent = distance.toFixed(0) + " u";
        document.getElementById('planetDiameter').textContent = (nearestPlanet.r * 2).toFixed(0) + " km";
        document.getElementById('planetTemp').textContent = nearestPlanet.temp || "Desconhecida";
        document.getElementById('planetAtmosphere').textContent = nearestPlanet.atmosphere || "Desconhecida";
        
        // Atualizar imagem do planeta
        if (cache[nearestPlanet.img]) {
            document.getElementById('planetImage').src = IMG_PATH + nearestPlanet.img;
        }
        
        // Atualizar barra de proximidade
        const proximityPercent = Math.min(100, (distance / 10000) * 100);
        document.getElementById('planetProximityBar').style.width = proximityPercent + "%";
        
        if (proximityPercent < 20) {
            document.getElementById('planetProximityBar').style.background = "linear-gradient(90deg, var(--danger), var(--ok))";
        } else if (proximityPercent < 50) {
            document.getElementById('planetProximityBar').style.background = "linear-gradient(90deg, #ff9500, var(--ok))";
        } else {
            document.getElementById('planetProximityBar').style.background = "linear-gradient(90deg, var(--ok), #00a8ff)";
        }
    }
    
    // Inicialização
    randomizeBodies();
    
    // Exportar funções para uso global
    window.planets = {
        drawPlanets,
        updatePlanetView,
        bodies
    };
})();