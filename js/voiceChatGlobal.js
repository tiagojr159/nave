// public/js/voiceChatGlobal.js
// Rádio Global: todos ouvem todos (broadcast via WebSocket)
// Depende dos elementos: #micPanel, #micToggleBtn, #micIndicator
(() => {
  const WS_HOST = (location.protocol === 'https:' ? 'wss://' : 'ws://') + 'ki6.com.br:8443';
  const BTN = () => document.getElementById('micToggleBtn');
  const IND = () => document.getElementById('micIndicator');
  const PANEL = () => document.getElementById('micPanel');

  let ws = null;
  let micStream = null;
  let mediaRecorder = null;
  let micActive = false;
  let reconnectTimer = null;

  // ———————————————————
  // Boot
  // ———————————————————
  document.addEventListener('DOMContentLoaded', () => {
    // garante painel na tela
    if (PANEL()) PANEL().style.display = 'flex';

    // botão ativa/desativa mic
    if (BTN()) {
      BTN().addEventListener('click', async () => {
        if (micActive) stopMic();
        else await startMic();
      });
    }

    // tecla J mostra/oculta painel (se quiser manter)
    document.addEventListener('keydown', (e) => {
      if (e.key.toLowerCase() === 'j' && PANEL()) {
        e.preventDefault();
        PANEL().style.display = (PANEL().style.display === 'none') ? 'flex' : 'none';
      }
    });

    connectWS();
  });

  // ———————————————————
  // WebSocket
  // ———————————————————
  function connectWS() {
    try {
      ws = new WebSocket(WS_HOST);
    } catch (err) {
      console.error('Falha ao criar WebSocket:', err);
      scheduleReconnect();
      return;
    }

    ws.onopen = () => {
      console.log('🛰️ WS conectado em', WS_HOST);
      clearTimeout(reconnectTimer);
    };

    ws.onclose = () => {
      console.warn('🔌 WS desconectado');
      scheduleReconnect();
    };

    ws.onerror = (e) => {
      console.warn('WS erro', e);
      ws.close();
    };

    ws.onmessage = (ev) => {
      // Recebe blocos base64 de áudio opus (ogg)
      const ab = base64ToArrayBuffer(ev.data);
      const blob = new Blob([ab], { type: 'audio/ogg; codecs=opus' });

      // Respeita política de reprodução: usa Audio() por evento de usuário
      const audio = new Audio();
      audio.src = URL.createObjectURL(blob);
      audio.play().catch(() => {
        // se o navegador travar o autoplay, tenta ao clicar em qualquer lugar
        const unlock = () => {
          audio.play().finally(() => document.removeEventListener('click', unlock));
        };
        document.addEventListener('click', unlock, { once: true });
      });
    };
  }

  function scheduleReconnect() {
    if (reconnectTimer) return;
    reconnectTimer = setTimeout(() => {
      reconnectTimer = null;
      connectWS();
    }, 1500);
  }

  // ———————————————————
  // Microfone
  // ———————————————————
  async function startMic() {
    try {
      // Captura áudio
      micStream = await navigator.mediaDevices.getUserMedia({ audio: true });

      // MediaRecorder em opus (ogg) – bom para latência e banda
      const mime = MediaRecorder.isTypeSupported('audio/ogg; codecs=opus')
        ? 'audio/ogg; codecs=opus'
        : 'audio/webm; codecs=opus';

      mediaRecorder = new MediaRecorder(micStream, { mimeType: mime });

      mediaRecorder.ondataavailable = (e) => {
        if (!e.data || e.data.size === 0) return;
        if (!ws || ws.readyState !== WebSocket.OPEN) return;

        blobToBase64(e.data).then(b64 => {
          // pacote pequeno, reduz delay (200ms é um bom meio-termo)
          ws.send(b64);
        });
      };

      mediaRecorder.start(200);
      micActive = true;
      if (IND()) IND().classList.add('active');
      if (BTN()) BTN().textContent = 'Desativar';
      window.shipPanel?.updateScannerObjects?.(); // só para forçar repaint se quiser
      window.showFeedback?.('🎤 Rádio Global Ligado');

    } catch (err) {
      console.error('Erro ao acessar microfone:', err);
      alert('Não foi possível ativar o microfone: ' + err.message);
    }
  }

  function stopMic() {
    try {
      if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
    } catch {}
    if (micStream) {
      for (const t of micStream.getTracks()) t.stop();
    }
    mediaRecorder = null;
    micStream = null;
    micActive = false;
    if (IND()) IND().classList.remove('active');
    if (BTN()) BTN().textContent = 'Ativar';
    window.showFeedback?.('🔇 Rádio Global Desligado');
  }

  // ———————————————————
  // Utils
  // ———————————————————
  function blobToBase64(blob) {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(reader.result.split(',')[1]);
      reader.readAsDataURL(blob);
    });
  }

  function base64ToArrayBuffer(base64) {
    const binary = atob(base64);
    const len = binary.length;
    const buffer = new ArrayBuffer(len);
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < len; i++) bytes[i] = binary.charCodeAt(i);
    return buffer;
  }

  // Exponho só se você quiser chamar manualmente em testes:
  window.voiceChatGlobal = { startMic, stopMic, connectWS };
})();
