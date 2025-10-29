<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\SocketServer;

class AudioBroadcast implements MessageComponentInterface {
    protected $clients;
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        echo "🎙️ Servidor de voz iniciado em wss://ki6.com.br:8443\n";
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

// 🚀 Configuração do servidor seguro (porta 8443)
$loop = Factory::create();

$context = [
    'local_cert'  => '/etc/ssl/certs/ssl-cert.crt',
    'local_pk'    => '/etc/ssl/certs/ssl-cert.key',
    'allow_self_signed' => true,
    'verify_peer' => false
];

// 🔹 Tenta localizar certificados padrão da HostGator (Let's Encrypt)
if (!file_exists($context['local_cert'])) {
    $context['local_cert'] = '/etc/pki/tls/certs/localhost.crt';
    $context['local_pk'] = '/etc/pki/tls/private/localhost.key';
}

$socket = new SocketServer('0.0.0.0:8443', [], $loop);
$secure = new SecureServer($socket, $loop, $context);

$server = new IoServer(
    new HttpServer(new WsServer(new AudioBroadcast())),
    $secure,
    $loop
);

$loop->run();
