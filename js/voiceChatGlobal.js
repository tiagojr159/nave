// 🎙️ Rádio Global — todos os jogadores falam e ouvem via WebSocket
// Funciona com o painel micPanel, micToggleBtn e micIndicator

(() => {
  const WS_HOST = (location.protocol === 'https:' ? 'wss://' : 'ws://') + 'ki6.com.br:8443';
  const BTN = () => document.getElementById('micToggleBtn');
  const IND = () => document.getElementById('micIndicator');
  const PANEL = () => document.getElementById('micPanel');

  let ws, micStream, audioCtx, processor, micAtivo = false;
  const buffers = {}; // múltiplos streams

  // 🔹 Conecta ao servidor WebSocket
  function connectWS() {
    ws = new WebSocket(WS_HOST);

    ws.onopen = () => console.log("🎧 Conectado ao servidor de voz:", WS_HOST);
    ws.onmessage = e => handleIncoming(e);
    ws.onclose = () => {
      console.warn("⚠️ Conexão WebSocket encerrada. Tentando reconectar...");
      setTimeout(connectWS, 2000);
    };
  }

  // 🔹 Trata áudio recebido (de qualquer jogador)
  function handleIncoming(event) {
    try {
      const msg = JSON.parse(event.data);
      if (msg.type === "audio" && msg.data) {
        playAudioChunk(msg.data);
      }
    } catch (err) {
      console.error("Erro ao processar áudio recebido:", err);
    }
  }

  // 🔹 Reproduz o áudio recebido
  async function playAudioChunk(base64Data) {
    try {
      if (!audioCtx) audioCtx = new AudioContext();
      const audioData = Uint8Array.from(atob(base64Data), c => c.charCodeAt(0)).buffer;
      const decoded = await audioCtx.decodeAudioData(audioData);
      const src = audioCtx.createBufferSource();
      src.buffer = decoded;
      src.connect(audioCtx.destination);
      src.start(0);
    } catch (e) {
      console.error("Erro ao tocar áudio:", e);
    }
  }

  // 🔹 Ativa ou desativa o microfone
  async function alternarMicrofone() {
    micAtivo = !micAtivo;

    if (micAtivo) {
      await ativarMicrofone();
      BTN().textContent = "Desativar";
      IND().classList.add("active");
      console.log("🎙️ Microfone ligado");
    } else {
      desativarMicrofone();
      BTN().textContent = "Ativar";
      IND().classList.remove("active");
      console.log("🔇 Microfone desligado");
    }
  }

  // 🔹 Ativa microfone e captura áudio
  async function ativarMicrofone() {
    try {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const source = audioCtx.createMediaStreamSource(micStream);
      processor = audioCtx.createScriptProcessor(4096, 1, 1);

      source.connect(processor);
      processor.connect(audioCtx.destination);

      processor.onaudioprocess = e => {
        if (!micAtivo || ws.readyState !== WebSocket.OPEN) return;

        const input = e.inputBuffer.getChannelData(0);
        const buffer = new ArrayBuffer(input.length * 2);
        const view = new DataView(buffer);

        // Converte Float32 para Int16
        for (let i = 0, offset = 0; i < input.length; i++, offset += 2) {
          const s = Math.max(-1, Math.min(1, input[i]));
          view.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
        }

        const base64 = btoa(String.fromCharCode(...new Uint8Array(buffer)));
        ws.send(JSON.stringify({ type: "audio", data: base64 }));
      };
    } catch (err) {
      console.error("Erro ao ativar microfone:", err);
    }
  }

  // 🔹 Desativa microfone
  function desativarMicrofone() {
    if (micStream) {
      micStream.getTracks().forEach(t => t.stop());
      micStream = null;
    }
    if (processor) processor.disconnect();
    if (audioCtx) audioCtx.close();
  }

  // 🔹 Inicializa
  document.addEventListener("DOMContentLoaded", () => {
    const btn = BTN();
    if (btn) btn.addEventListener("click", alternarMicrofone);
    connectWS();
  });
})();
