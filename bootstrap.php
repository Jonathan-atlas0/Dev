<?php
/**
 * bootstrap.php
 * Monta CommandBus, QueryBus e RedisService.
 * Inclua APÓS Conexao.php (que fornece $conn).
 */

declare(strict_types=1);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// ── Interfaces ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/cqrs/CommandInterface.php';
require_once __DIR__ . '/cqrs/QueryInterface.php';
require_once __DIR__ . '/cqrs/CommandHandlerInterface.php';
require_once __DIR__ . '/cqrs/QueryHandlerInterface.php';

// ── Bus ───────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/cqrs/Bus/CommandBus.php';
require_once __DIR__ . '/cqrs/Bus/QueryBus.php';

// ── Commands ──────────────────────────────────────────────────────────────────
require_once __DIR__ . '/cqrs/Commands/AdicionarAoCarrinhoCommand.php';
require_once __DIR__ . '/cqrs/Commands/LimparCarrinhoCommand.php';
require_once __DIR__ . '/cqrs/Commands/FinalizarPedidoCommand.php';
require_once __DIR__ . '/cqrs/Commands/RegistrarPagamentoCommand.php';

// ── Queries ───────────────────────────────────────────────────────────────────
require_once __DIR__ . '/cqrs/Queries/BuscarCarrinhoQuery.php';
require_once __DIR__ . '/cqrs/Queries/BuscarPedidosFinalizadosQuery.php';
require_once __DIR__ . '/cqrs/Queries/BuscarPagamentosQuery.php';

// ── Handlers ──────────────────────────────────────────────────────────────────
require_once __DIR__ . '/cqrs/Handlers/AdicionarAoCarrinhoHandler.php';
require_once __DIR__ . '/cqrs/Handlers/LimparCarrinhoHandler.php';
require_once __DIR__ . '/cqrs/Handlers/FinalizarPedidoHandler.php';
require_once __DIR__ . '/cqrs/Handlers/BuscarCarrinhoHandler.php';
require_once __DIR__ . '/cqrs/Handlers/BuscarPedidosFinalizadosHandler.php';
require_once __DIR__ . '/cqrs/Handlers/RegistrarPagamentoHandler.php';
require_once __DIR__ . '/cqrs/Handlers/BuscarPagamentosHandler.php';

// ── Cache ─────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/cache/RedisService.php';

use Cafeteria\Cache\RedisService;
use Cafeteria\CQRS\Bus\CommandBus;
use Cafeteria\CQRS\Bus\QueryBus;
use Cafeteria\CQRS\Commands\AdicionarAoCarrinhoCommand;
use Cafeteria\CQRS\Commands\LimparCarrinhoCommand;
use Cafeteria\CQRS\Commands\FinalizarPedidoCommand;
use Cafeteria\CQRS\Commands\RegistrarPagamentoCommand;
use Cafeteria\CQRS\Queries\BuscarCarrinhoQuery;
use Cafeteria\CQRS\Queries\BuscarPedidosFinalizadosQuery;
use Cafeteria\CQRS\Queries\BuscarPagamentosQuery;
use Cafeteria\CQRS\Handlers\AdicionarAoCarrinhoHandler;
use Cafeteria\CQRS\Handlers\LimparCarrinhoHandler;
use Cafeteria\CQRS\Handlers\FinalizarPedidoHandler;
use Cafeteria\CQRS\Handlers\BuscarCarrinhoHandler;
use Cafeteria\CQRS\Handlers\BuscarPedidosFinalizadosHandler;
use Cafeteria\CQRS\Handlers\RegistrarPagamentoHandler;
use Cafeteria\CQRS\Handlers\BuscarPagamentosHandler;

$redis = new RedisService();

// ── Command Bus ───────────────────────────────────────────────────────────────
$commandBus = new CommandBus();
$commandBus->register(AdicionarAoCarrinhoCommand::class, new AdicionarAoCarrinhoHandler($conn, $redis));
$commandBus->register(LimparCarrinhoCommand::class,      new LimparCarrinhoHandler($conn, $redis));
$commandBus->register(FinalizarPedidoCommand::class,     new FinalizarPedidoHandler($conn, $redis));
$commandBus->register(RegistrarPagamentoCommand::class,  new RegistrarPagamentoHandler($conn, $redis));

// ── Query Bus ─────────────────────────────────────────────────────────────────
$queryBus = new QueryBus();
$queryBus->register(BuscarCarrinhoQuery::class,           new BuscarCarrinhoHandler($conn, $redis));
$queryBus->register(BuscarPedidosFinalizadosQuery::class, new BuscarPedidosFinalizadosHandler($conn));
$queryBus->register(BuscarPagamentosQuery::class,         new BuscarPagamentosHandler($conn));
