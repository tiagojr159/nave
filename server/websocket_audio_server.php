<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SocketServer;
use React\Socket\SecureServer;

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

// 🔹 Inicia o loop principal
$loop = Factory::create();

// 🔹 Detecta certificado SSL automaticamente
$possibleCerts = [
    '/etc/letsencrypt/live/ki6.com.br/fullchain.pem' => '/etc/letsencrypt/live/ki6.com.br/privkey.pem',
    '/etc/pki/tls/certs/localhost.crt' => '/etc/pki/tls/private/localhost.key',
    '/etc/ssl/certs/ssl-cert.crt' => '/etc/ssl/certs/ssl-cert.key',
    '/var/cpanel/ssl/certs/ki6.com.br.crt' => '/var/cpanel/ssl/keys/ki6.com.br.key',
];

$found = false;
foreach ($possibleCerts as $cert => $key) {
    if (file_exists($cert) && file_exists($key)) {
        $context = [
            'local_cert' => $cert,
            'local_pk' => $key,
            'allow_self_signed' => true,
            'verify_peer' => false
        ];
        $found = true;
        echo "✅ Certificado SSL detectado: $cert\n";
        break;
    }
}

if (!$found) {
    echo "⚠️ Nenhum certificado SSL válido encontrado. Abortando servidor.\n";
    exit(1);
}

// 🔹 Inicializa o servidor HTTPS seguro
try {
    $socket = new SocketServer('0.0.0.0:8090', [], $loop);
    $secure = new SecureServer($socket, $loop, $context);

    new IoServer(
        new HttpServer(new WsServer(new AudioBroadcast())),
        $secure,
        $loop
    );

    $loop->run();
} catch (Exception $e) {
    echo "❌ Falha ao iniciar servidor seguro: {$e->getMessage()}\n";
}
