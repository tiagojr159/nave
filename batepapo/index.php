<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Bate-papo por Voz (WebRTC)</title>
</head>
<body>
<h2>Chat de Voz</h2>
<button id="start">Iniciar</button>
<button id="call">Chamar</button>
<audio id="localAudio" autoplay muted></audio>
<audio id="remoteAudio" autoplay></audio>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
let localStream, peer;

// Servidor de sinalização em PHP (simples com AJAX)
const SIGNAL_URL = "signaling.php";

// Identificador do usuário (A ou B)
const myId = prompt("Seu nome ou ID:");
const peerId = myId === "A" ? "B" : "A";

$("#start").click(async ()=>{
  localStream = await navigator.mediaDevices.getUserMedia({ audio:true });
  $("#localAudio").prop("srcObject", localStream);
  alert("Microfone ativado");
});

$("#call").click(async ()=>{
  peer = new RTCPeerConnection();
  localStream.getTracks().forEach(t => peer.addTrack(t, localStream));

  peer.ontrack = e => $("#remoteAudio").prop("srcObject", e.streams[0]);

  const offer = await peer.createOffer();
  await peer.setLocalDescription(offer);
  $.post(SIGNAL_URL, {from: myId, to: peerId, data: JSON.stringify(offer)});

  // Escuta respostas
  setInterval(async ()=>{
    const res = await $.getJSON(SIGNAL_URL+"?from="+myId+"&to="+peerId);
    if(res && res.type === "answer"){
      await peer.setRemoteDescription(new RTCSessionDescription(res));
    }
  },2000);
});

// Responder a chamadas
setInterval(async ()=>{
  const data = await $.getJSON(SIGNAL_URL+"?from="+peerId+"&to="+myId);
  if(data && data.type === "offer"){
    peer = new RTCPeerConnection();
    localStream.getTracks().forEach(t => peer.addTrack(t, localStream));
    peer.ontrack = e => $("#remoteAudio").prop("srcObject", e.streams[0]);

    await peer.setRemoteDescription(new RTCSessionDescription(data));
    const answer = await peer.createAnswer();
    await peer.setLocalDescription(answer);
    $.post(SIGNAL_URL, {from: myId, to: peerId, data: JSON.stringify(answer)});
  }
},2000);
</script>
</body>
</html>
