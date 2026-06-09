<?php
/**
 * websocket/server.php
 * ─────────────────────
 * Servidor WebSocket que:
 *  1. Aceita conexões de clientes (painel Admin)
 *  2. Escuta o canal Redis "cafeteria:pedidos:novo" via Pub/Sub
 *  3. Quando chega um evento, transmite para todos os clientes conectados
 *
 * Roda no container `ws`: php /var/www/html/websocket/server.php
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Loop;

// ── WebSocket App ─────────────────────────────────────────────────────────────

class PedidosWebSocket implements MessageComponentInterface
{
    /** @var \SplObjectStorage<ConnectionInterface, null> */
    protected \SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        echo "[WS] Servidor iniciado. Aguardando conexões...\n";
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        echo "[WS] Nova conexão: #{$conn->resourceId} (total: {$this->clients->count()})\n";

        // Envia confirmação de conexão
        $conn->send(json_encode([
            'tipo'      => 'conectado',
            'mensagem'  => 'Painel Admin conectado ao servidor de eventos.',
            'horario'   => date('H:i:s'),
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        // Admin pode enviar ping para manter conexão viva
        echo "[WS] Mensagem de #{$from->resourceId}: $msg\n";
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        echo "[WS] Conexão fechada: #{$conn->resourceId} (total: {$this->clients->count()})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "[WS] Erro #{$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Transmite um payload JSON para todos os clientes conectados.
     */
    public function broadcast(array $payload): void
    {
        $json = json_encode($payload);
        echo "[WS] Broadcast para {$this->clients->count()} cliente(s): $json\n";

        foreach ($this->clients as $client) {
            $client->send($json);
        }
    }
}

// ── Setup ─────────────────────────────────────────────────────────────────────

$loop = Loop::get();
$app  = new PedidosWebSocket();

// Servidor WebSocket na porta 8082
$server = IoServer::factory(
    new HttpServer(new WsServer($app)),
    8082,
    '0.0.0.0',
    $loop
);

echo "[WS] Escutando WebSocket em 0.0.0.0:8082\n";

// ── Redis Subscriber (via stream não-bloqueante) ──────────────────────────────

$redisHost = getenv('REDIS_HOST') ?: 'redis';
$redisPort = (int)(getenv('REDIS_PORT') ?: 6379);

$subscriber = new \Redis();
$subscriber->connect($redisHost, $redisPort);
$subscriber->setOption(\Redis::OPT_READ_TIMEOUT, -1); // sem timeout

echo "[WS] Conectado ao Redis em {$redisHost}:{$redisPort}\n";
echo "[WS] Inscrito no canal: cafeteria:pedidos:novo\n";

// Adiciona o socket do Redis ao event loop do ReactPHP
$redisSocket = $subscriber->getDbNum(); // dummy — usamos stream direto

// Usamos um timer que tenta processar mensagens Redis sem bloquear o loop
$loop->addPeriodicTimer(0.1, function () use ($subscriber, $app) {
    // phpredis com subscribe é bloqueante, então usamos uma Redis separada
    // com blpop em uma lista como alternativa não-bloqueante
});

// Abordagem correta: processo separado de subscribe que escreve em uma fila
// e o loop lê a fila. Aqui usamos um socket Redis separado com select.
$redisSub = new \Redis();
$redisSub->connect($redisHost, $redisPort);

// Executa subscribe num timer de leitura periódica via socket nativo
$socket = fsockopen($redisHost, $redisPort, $errno, $errstr, 5);
if (!$socket) {
    die("[WS] Falha ao conectar Redis via socket: $errstr\n");
}

stream_set_blocking($socket, false);

// Envia SUBSCRIBE (comprimento do canal deve ser 22)
fwrite($socket, "*2\r\n\$9\r\nSUBSCRIBE\r\n\$22\r\ncafeteria:pedidos:novo\r\n");

$buffer = '';

$loop->addReadStream($socket, function ($socket) use ($app, &$buffer) {
    $chunk = fread($socket, 4096);
    if ($chunk === false || $chunk === '') return;

    $buffer .= $chunk;

    // Processa mensagens completas do protocolo RESP
    while (($pos = strpos($buffer, "\r\n")) !== false) {
        // Tenta extrair payload JSON de uma mensagem de subscribe
        if (preg_match('/\{.+\}/s', $buffer, $m)) {
            $payload = json_decode($m[0], true);
            if (is_array($payload)) {
                echo "[WS] Evento recebido do Redis: " . json_encode($payload) . "\n";
                $app->broadcast($payload);
            }
            // Remove o JSON processado do buffer
            $buffer = substr($buffer, strpos($buffer, $m[0]) + strlen($m[0]));
        } else {
            // Remove linha processada
            $buffer = substr($buffer, $pos + 2);
        }
    }
});

echo "[WS] Event loop iniciado.\n";
$loop->run();
