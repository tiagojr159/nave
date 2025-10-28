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
        echo "🎙️ Servidor de áudio inicializado (WSS habilitado)...\n";
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

$loop = Factory::create();
$port = 8443;

// 🔒 Caminhos do certificado Let's Encrypt
$local_cert = '/etc/letsencrypt/live/ki6.com.br/fullchain.pem';
$local_pk   = '/etc/letsencrypt/live/ki6.com.br/privkey.pem';

if (!file_exists($local_cert) || !file_exists($local_pk)) {
    die("❌ Certificados SSL não encontrados! Verifique o caminho.\n");
}

$socket = new SocketServer("0.0.0.0:{$port}", [], $loop);
$secureSocket = new SecureServer($socket, $loop, [
    'local_cert'        => $local_cert,
    'local_pk'          => $local_pk,
    'allow_self_signed' => false,
    'verify_peer'       => false
]);

$server = new IoServer(
    new HttpServer(new WsServer(new AudioBroadcast())),
    $secureSocket,
    $loop
);

echo "🌐 Servidor WSS rodando em porta {$port}\n";
$loop->run();
