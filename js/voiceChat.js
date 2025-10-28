// 🎙️ voiceChat.js — Comunicação de Voz Multiplayer
let localStream = null;
let peers = {}; // conexões ativas
let myId = null;
const SIGNAL_URL = "batepapo/signaling.php";

async function initVoiceChat(userId) {
  myId = userId;
  console.log("🎙️ Iniciando voz multiplayer como:", myId);

  try {
    localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    console.log("✅ Microfone ativo");
  } catch (err) {
    alert("⚠️ Não foi possível acessar o microfone.");
    console.error(err);
    return;
  }

  // Envia áudio local para painel, se houver
  const localAudio = document.createElement("audio");
  localAudio.srcObject = localStream;
  localAudio.muted = true;
  localAudio.autoplay = true;
  document.body.appendChild(localAudio);

  // Escuta sinais de outros jogadores
  setInterval(checkSignals, 2000);
}

// Envia mensagem de sinalização para outro player
function sendSignal(to, data) {
  $.post(SIGNAL_URL, { from: myId, to, data: JSON.stringify(data) });
}

// Checa se há mensagens para mim
async function checkSignals() {
  const res = await $.getJSON(SIGNAL_URL + "?from=" + myId);
  if (!res || res.length === 0) return;
  for (const msg of res) handleSignal(msg);
}

// Processa mensagens recebidas
async function handleSignal(data) {
  const from = data.from;
  let pc = peers[from];

  if (!pc) {
    pc = createPeerConnection(from);
    peers[from] = pc;
  }

  if (data.type === "offer") {
    await pc.setRemoteDescription(new RTCSessionDescription(data));
    const answer = await pc.createAnswer();
    await pc.setLocalDescription(answer);
    sendSignal(from, answer);
  } else if (data.type === "answer") {
    await pc.setRemoteDescription(new RTCSessionDescription(data));
  } else if (data.type === "candidate") {
    try {
      await pc.addIceCandidate(new RTCIceCandidate(data.candidate));
    } catch (e) {
      console.warn("Erro ICE:", e);
    }
  }
}

// Cria conexão WebRTC
function createPeerConnection(peerId) {
  const pc = new RTCPeerConnection();

  // Adiciona trilhas locais
  localStream.getTracks().forEach(t => pc.addTrack(t, localStream));

  // Exibe áudio remoto
  pc.ontrack = e => {
    const remote = document.createElement("audio");
    remote.srcObject = e.streams[0];
    remote.autoplay = true;
    remote.dataset.peer = peerId;
    document.body.appendChild(remote);
    console.log("🔊 Recebendo áudio de", peerId);
  };

  // ICE Candidate
  pc.onicecandidate = e => {
    if (e.candidate) {
      sendSignal(peerId, { type: "candidate", candidate: e.candidate });
    }
  };

  return pc;
}

// Faz chamada para outro player
async function callPeer(peerId) {
  const pc = createPeerConnection(peerId);
  peers[peerId] = pc;

  const offer = await pc.createOffer();
  await pc.setLocalDescription(offer);
  sendSignal(peerId, offer);
  console.log("📡 Oferta enviada para", peerId);
}
