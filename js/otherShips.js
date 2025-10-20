// otherShips.js - Camada de outras naves (NPCs)
(function() {
    // Definição das naves NPCs com as imagens especificadas
    const npcs = [
        // Estações espaciais
        { id: 1, name: "Estação Comercial Alpha", x: 15000, y: 25000, type: "station", img: "estacao.png", friendly: true },
        { id: 2, name: "Estação de Pesquisa Beta", x: 60000, y: 35000, type: "station", img: "estacao.png", friendly: true },
        { id: 3, name: "Estação de Mineração Gama", x: 75000, y: 20000, type: "station", img: "estacao.png", friendly: true },
        
        // Naves de carga
        { id: 4, name: "Nave de Carga Delta", x: 30000, y: 40000, type: "cargo", img: "navecarga.png", friendly: true },
        { id: 5, name: "Nave de Carga Épsilon", x: 45000, y: 55000, type: "cargo", img: "navecarga.png", friendly: true },
        { id: 6, name: "Nave de Carga Zeta", x: 20000, y: 65000, type: "cargo", img: "navecarga.png", friendly: true },
        
        // Sondas de exploração
        { id: 7, name: "Sonda Alfa", x: 20000, y: 15000, type: "probe", img: "sonda.png", friendly: true },
        { id: 8, name: "Sonda Beta", x: 35000, y: 20000, type: "probe", img: "sonda.png", friendly: true },
        { id: 9, name: "Sonda Gama", x: 18000, y: 32000, type: "probe", img: "sonda.png", friendly: true },
        { id: 10, name: "Sonda Delta", x: 42000, y: 28000, type: "probe", img: "sonda.png", friendly: true },
        { id: 11, name: "Sonda Épsilon", x: 25000, y: 38000, type: "probe", img: "sonda.png", friendly: true },
        { id: 12, name: "Sonda Zeta", x: 38000, y: 15000, type: "probe", img: "sonda.png", friendly: true },
        { id: 13, name: "Sonda Eta", x: 15000, y: 45000, type: "probe", img: "sonda.png", friendly: true },
        { id: 14, name: "Sonda Theta", x: 45000, y: 35000, type: "probe", img: "sonda.png", friendly: true },
        { id: 15, name: "Sonda Iota", x: 55000, y: 25000, type: "probe", img: "sonda.png", friendly: true },
        { id: 16, name: "Sonda Kappa", x: 32000, y: 18000, type: "probe", img: "sonda.png", friendly: true },
        { id: 17, name: "Sonda Lambda", x: 68000, y: 42000, type: "probe", img: "sonda.png", friendly: true },
        { id: 18, name: "Sonda Mu", x: 28000, y: 52000, type: "probe", img: "sonda.png", friendly: true },
        { id: 19, name: "Sonda Nu", x: 48000, y: 18000, type: "probe", img: "sonda.png", friendly: true },
        { id: 20, name: "Sonda Xi", x: 38000, y: 62000, type: "probe", img: "sonda.png", friendly: true }
    ];
    
    // Cache para as imagens
    const imageCache = {};
    
    // Função para carregar uma imagem
    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.src = IMG_PATH + src;
            img.onload = () => resolve(img);
            img.onerror = () => {
                console.warn("Falha ao carregar imagem:", src);
                resolve(null);
            };
        });
    }
    
    // Carregar todas as imagens necessárias
    async function loadImages() {
        const uniqueImages = [...new Set(npcs.map(npc => npc.img))];
        await Promise.all(uniqueImages.map(async imgSrc => {
            const img = await loadImage(imgSrc);
            if (img) {
                imageCache[imgSrc] = img;
            }
        }));
        console.log("Imagens de NPCs carregadas:", Object.keys(imageCache));
    }
    
    function drawOtherShips(ctx, width, height) {
        for(const npc of npcs) {
            const dx = npc.x - ship.x;
            const dy = npc.y - ship.y;
            const dist = Math.hypot(dx, dy);
            
            if(dist > MAX_DRAW_DIST) continue;
            
            // Calcular posição na tela
            const angToNpc = Math.atan2(dy, dx);
            let rel = angToNpc - (ship.heading * Math.PI / 180);
            rel = Math.atan2(Math.sin(rel), Math.cos(rel));
            
            const screenX = (width/2) + (rel * (width/Math.PI));
            const screenY = height*0.6 + ship.pitch*3;
            
            // Tamanho aparente
            const scale = Math.min(Math.max((FOV / Math.max(60, dist)), 0.02), 3.5);
            let size = 40 * scale; // Tamanho base para as imagens
            
            // Ajustar tamanho baseado no tipo
            if (npc.type === "station") {
                size = 60 * scale;
            } else if (npc.type === "probe") {
                size = 25 * scale;
            }
            
            // Desenhar nave NPC usando a imagem
            const img = imageCache[npc.img];
            if (img) {
                ctx.save();
                ctx.translate(screenX, screenY);
                
                // Desenhar a imagem
                const d = size;
                ctx.drawImage(img, -d/2, -d/2, d, d);
                
                // Adicionar um indicador de amizade/inimizade
                if (!npc.friendly) {
                    ctx.strokeStyle = '#ff5555';
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    ctx.arc(0, 0, d/2 + 5, 0, Math.PI * 2);
                    ctx.stroke();
                }
                
                ctx.restore();
            } else {
                // Fallback se a imagem não carregou
                ctx.save();
                ctx.translate(screenX, screenY);
                
                // Desenhar um círculo colorido como fallback
                ctx.fillStyle = npc.friendly ? '#50fa7b' : '#ff5555';
                ctx.beginPath();
                ctx.arc(0, 0, size/2, 0, Math.PI * 2);
                ctx.fill();
                
                ctx.restore();
            }
            
            // Nome se grande o suficiente
            if(size > 20) {
                ctx.fillStyle = '#8aa2ff';
                ctx.font = '10px system-ui, Arial';
                ctx.textAlign='center';
                ctx.fillText(npc.name, screenX, screenY - size/2 - 5);
            }
        }
    }
    
    function updateNPCs(dt) {
        // Lógica de movimento para NPCs
        for(const npc of npcs) {
            if (npc.type === "probe") {
                // Sondas se movem em padrões específicos
                const time = performance.now() / 1000;
                const speed = 80; // velocidade base das sondas
                
                // Movimento circular para algumas sondas
                if (npc.id % 3 === 0) {
                    const radius = 3000 + (npc.id * 700);
                    const centerX = npc.x + Math.cos(time * 0.2 + npc.id) * radius;
                    const centerY = npc.y + Math.sin(time * 0.2 + npc.id) * radius;
                    npc.x = centerX;
                    npc.y = centerY;
                } 
                // Movimento em oito para outras
                else if (npc.id % 3 === 1) {
                    npc.x += Math.cos(time * 0.3 + npc.id) * speed * dt;
                    npc.y += Math.sin(time * 0.6 + npc.id) * speed * dt;
                }
                // Movimento aleatório para as restantes
                else {
                    npc.x += (Math.random() - 0.5) * speed * dt;
                    npc.y += (Math.random() - 0.5) * speed * dt;
                }
                
                // Manter dentro dos limites do mundo
                npc.x = Math.max(1000, Math.min(WORLD_W - 1000, npc.x));
                npc.y = Math.max(1000, Math.min(WORLD_H - 1000, npc.y));
            } else if (npc.type === "cargo") {
                // Naves de carga se movem entre pontos fixos
                const time = performance.now() / 1000;
                const route = Math.floor(time / 15) % 4; // muda de rota a cada 15 segundos
                
                if (route === 0) {
                    npc.x += 80 * dt;
                } else if (route === 1) {
                    npc.y += 80 * dt;
                } else if (route === 2) {
                    npc.x -= 80 * dt;
                } else {
                    npc.y -= 80 * dt;
                }
            } else if (npc.type === "station") {
                // Estações não se movem, mas podem rotacionar lentamente
                // Estações permanecem fixas no espaço
            }
        }
    }
    
    // Função para adicionar mais NPCs dinamicamente
    function addNPCs(type, count) {
        const names = {
            station: ["Estação", "Posto Avançado", "Base Espacial", "Plataforma Orbital"],
            cargo: ["Nave de Carga", "Transportador", "Cargueiro", "Navio de Suprimentos"],
            probe: ["Sonda", "Explorador", "Satélite", "Robô Sonda"]
        };
        
        const nextId = npcs.length > 0 ? Math.max(...npcs.map(n => n.id)) + 1 : 1;
        const typeNames = names[type] || ["Unidade"];
        
        for (let i = 0; i < count; i++) {
            const id = nextId + i;
            const nameIndex = Math.floor(Math.random() * typeNames.length);
            const name = `${typeNames[nameIndex]} ${String.fromCharCode(65 + (id % 26))}`;
            
            // Posição aleatória no mapa
            const x = Math.random() * WORLD_W;
            const y = Math.random() * WORLD_H;
            
            npcs.push({
                id: id,
                name: name,
                x: x,
                y: y,
                type: type,
                img: type === "station" ? "estacao.png" : 
                     type === "cargo" ? "navecarga.png" : "sonda.png",
                friendly: true
            });
        }
        
        console.log(`Adicionadas ${count} novas unidades do tipo ${type}. Total: ${npcs.length}`);
    }
    
    // Inicialização
    // Carregar imagens
    loadImages().then(() => {
        console.log("Módulo otherShips inicializado com", npcs.length, "NPCs");
    });
    
    // Exportar funções para uso global
    window.otherShips = {
        drawOtherShips,
        updateNPCs,
        addNPCs,
        npcs,
        loadImages
    };
})();