<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SocketServer;
use React\EventLoop\Factory;

class AudioBroadcast implements MessageComponentInterface {
    protected $clients;
    public function __construct() {
        $this->clients = new \SplObjectStorage;
echo "🎙️ Servidor de voz iniciado em ws://0.0.0.0:8080\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "🛰️ Nova conexão: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        foreach ($this->clients as $client) {
            if ($client !== $from) $client->send($msg);
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

$loop = Factory::create();
$socket = new SocketServer("0.0.0.0:8080", [], $loop);
$server = new IoServer(new HttpServer(new WsServer(new AudioBroadcast())), $socket, $loop);
$loop->run();
