<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;
use React\Socket\SecureServer;
use React\EventLoop\Factory;

class AudioBroadcast implements MessageComponentInterface {
    protected $clients;
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "🎙️ Servidor WSS de voz iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "🛰️ Cliente conectado: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "❌ Cliente desconectado: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "⚠️ Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

$loop = Factory::create();
$port = 8443;

// 🔒 Caminho dos certificados SSL do Let's Encrypt
$cert = '/etc/letsencrypt/live/ki6.com.br/fullchain.pem';
$key  = '/etc/letsencrypt/live/ki6.com.br/privkey.pem';

if (!file_exists($cert) || !file_exists($key)) {
    die("❌ Certificados SSL não encontrados!\n");
}

$socket = new SocketServer("0.0.0.0:{$port}", [], $loop);
$secureSocket = new SecureServer($socket, $loop, [
    'local_cert'        => $cert,
    'local_pk'          => $key,
    'allow_self_signed' => false,
    'verify_peer'       => false
]);

$server = new IoServer(
    new HttpServer(new WsServer(new AudioBroadcast())),
    $secureSocket,
    $loop
);

echo "🌐 Servidor WebSocket seguro (WSS) escutando em porta {$port}\n";
$loop->run();
