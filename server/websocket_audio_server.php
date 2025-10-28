<?php
// 🎧 Servidor WebSocket de Áudio Global (Ratchet)
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;

class AudioBroadcast implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "🎙️ Servidor de áudio iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "🛰️ Nova conexão: {$conn->resourceId}\n";
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
        echo "❌ Conexão encerrada: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "⚠️ Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

$port = 8443;
$socket = new SocketServer("0.0.0.0:{$port}");

$server = new IoServer(
    new HttpServer(new WsServer(new AudioBroadcast())),
    $socket
);

echo "🌐 Servidor WebSocket ouvindo em porta {$port}\n";
$server->run();
