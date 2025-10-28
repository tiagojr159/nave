<?php
// server/websocket_audio_server.php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;

class AudioBroadcast implements MessageComponentInterface {
    protected \SplObjectStorage $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
        echo "🎧 Servidor de áudio iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Novo cliente. Total: " . count($this->clients) . "\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // repassa base64 diretamente; o cliente cria o Blob e toca
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Cliente saiu. Total: " . count($this->clients) . "\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

$port = 8443; // mantenha igual ao usado no JS
$server = IoServer::factory(
    new HttpServer(new WsServer(new AudioBroadcast())),
    $port
);

echo "🛰️ Escutando WS na porta {$port}\n";
$server->run();
