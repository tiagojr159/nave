// shipPanel.js - Camada do painel da nave
(function() {
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
        
        // Função para atualizar o HUD
        function updateHUD(nearestInfo) {
            hudVel.textContent = ship.vel.toFixed(0);
            hudHdg.textContent = ((ship.heading % 360 + 360) % 360).toFixed(0) + '°';
            hudPos.textContent = `${ship.x.toFixed(0)},${ship.y.toFixed(0)}`;
            
            if (targetWaypoint) {
                const dx = targetWaypoint.x - ship.x;
                const dy = targetWaypoint.y - ship.y;
                const dist = Math.hypot(dx, dy);
                hudTgt.textContent = `${targetWaypoint.name} (${dist.toFixed(0)}px)`;
            } else {
                hudTgt.textContent = `${nearestInfo.nearestName} (${nearestInfo.nearestDist.toFixed(0)}px)`;
            }
            
            hudEnergy.textContent = Math.round(ship.boostEnergy) + '%';
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
                    const dist = Math.sqrt(dx*dx + dy*dy);
                    
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
            
            // Verificar se planets.bodies existe
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
                        
                        // Calcular posição no scanner
                        const radius = 35;
                        const centerX = 50;
                        const centerY = 50;
                        
                        const x = centerX + radius * Math.cos(angle);
                        const y = centerY + radius * Math.sin(angle);
                        
                        objElement.style.left = `${x}px`;
                        objElement.style.top = `${y}px`;
                        objElement.style.transform = 'translate(-50%, -50%)';
                        
                        // Cor baseada no tipo
                        if (body.name === "Sol") {
                            objElement.style.background = 'rgba(255,200,0,0.9)';
                        } else {
                            objElement.style.background = 'rgba(0,255,255,0.9)';
                        }
                        
                        // Tamanho baseado na distância
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
                const distance = Math.sqrt(dx*dx + dy*dy);
                
                if (distance < 30000) {
                    const angle = Math.atan2(dy, dx);
                    
                    const objElement = document.createElement('div');
                    objElement.className = 'scanner-object';
                    
                    // Calcular posição no scanner
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
        
        console.log("Painel da nave inicializado com sucesso");
        return true;
    }
    
    // Verificar se o DOM está carregado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShipPanel);
    } else {
        initShipPanel();
    }
})();