// ===============================
// 📱 CONTROLES POR TOQUE (MOBILE)
// ===============================
function setupTouchControls() {
  const boostBtn = document.getElementById('boostBtn');
  const brakeBtn = document.getElementById('brakeBtn');

  let touchingLeft = false;
  let touchingRight = false;

  // Botões turbo e freio
  if (boostBtn) {
    boostBtn.addEventListener('touchstart', e => {
      e.preventDefault();
      keys['shift'] = true;
    });
    boostBtn.addEventListener('touchend', e => {
      e.preventDefault();
      keys['shift'] = false;
    });
  }

  if (brakeBtn) {
    brakeBtn.addEventListener('touchstart', e => {
      e.preventDefault();
      keys['b'] = true;
    });
    brakeBtn.addEventListener('touchend', e => {
      e.preventDefault();
      keys['b'] = false;
    });
  }

  // Área de toque para girar nave (metade da tela)
  const view = document.getElementById('view');
  if (view) {
    view.addEventListener('touchstart', e => {
      const touch = e.touches[0];
      const mid = window.innerWidth / 2;
      if (touch.clientX < mid) touchingLeft = true;
      else touchingRight = true;
    });

    view.addEventListener('touchend', () => {
      touchingLeft = false;
      touchingRight = false;
    });
  }

  // Atualiza direção enquanto o jogador toca na tela
  function handleTouchRotation(dt) {
    if (touchingLeft) ship.heading -= ship.turnRate * dt;
    if (touchingRight) ship.heading += ship.turnRate * dt;
  }

  // Integra no loop principal
  const oldUpdate = window.update;
  window.update = function(dt) {
    handleTouchRotation(dt);
    if (typeof oldUpdate === 'function') oldUpdate(dt);
  };

  console.log("🎮 Controles de toque ativados!");
}

setupTouchControls();
