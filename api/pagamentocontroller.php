<?php

/**
 * API de Pagamentos — /api/pagamentocontroller.php
 * Instrumentada com Monolog (logs) e Prometheus (métricas) — Aula 11.
 */

declare(strict_types=1);

require_once __DIR__ . '/../Conexao.php';
require_once __DIR__ . '/../bootstrap.php';

use Cafeteria\CQRS\Commands\RegistrarPagamentoCommand;
use Cafeteria\CQRS\Queries\BuscarPagamentosQuery;
use Cafeteria\CQRS\Handlers\RegistrarPagamentoHandler;
use Cafeteria\CQRS\Handlers\BuscarPagamentosHandler;
use Cafeteria\Observability\LoggerService;
use Cafeteria\Observability\MetricsService;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$rota   = '/api/pagamentocontroller.php';
$inicio = microtime(true);

$commandBus->register(
    RegistrarPagamentoCommand::class,
    new RegistrarPagamentoHandler($conn, $redis)
);
$queryBus->register(
    BuscarPagamentosQuery::class,
    new BuscarPagamentosHandler($conn)
);

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$nome   = $_GET['nome']   ?? null;
$status = $_GET['status'] ?? null;
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

// ── GET ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $pagamentos = $queryBus->dispatch(new BuscarPagamentosQuery(
            nomeUsuario: $nome,
            status:      $status,
        ));
        MetricsService::incrementarRequisicaoHttp($method, $rota, 200);
        echo json_encode($pagamentos);
    } catch (\Throwable $e) {
        LoggerService::error('Erro ao buscar pagamentos', ['exception' => $e->getMessage()]);
        MetricsService::incrementarRequisicaoHttp($method, $rota, 500);
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

// ── POST ────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $campos = ['nome', 'metodo', 'valor'];
    foreach ($campos as $campo) {
        if (!isset($body[$campo])) {
            MetricsService::incrementarRequisicaoHttp($method, $rota, 400);
            http_response_code(400);
            echo json_encode(['erro' => "Campo obrigatório ausente: $campo"]);
            exit;
        }
    }

    $metodosValidos = ['pix', 'cartao', 'dinheiro'];
    if (!in_array($body['metodo'], $metodosValidos, true)) {
        MetricsService::incrementarRequisicaoHttp($method, $rota, 400);
        http_response_code(400);
        echo json_encode(['erro' => "Método inválido. Use: " . implode(', ', $metodosValidos)]);
        exit;
    }

    try {
        $commandBus->dispatch(new RegistrarPagamentoCommand(
            nomeUsuario:     $body['nome'],
            metodoPagamento: $body['metodo'],
            valor:           (float) $body['valor'],
        ));

        LoggerService::info('Pagamento registrado', [
            'usuario' => $body['nome'],
            'metodo'  => $body['metodo'],
            'valor'   => $body['valor'],
            'duracao_ms' => round((microtime(true) - $inicio) * 1000, 2),
        ]);

        MetricsService::incrementarRequisicaoHttp($method, $rota, 201);
        http_response_code(201);
        echo json_encode(['mensagem' => 'Pagamento registrado com sucesso.', 'status' => 'pendente']);
    } catch (\Throwable $e) {
        LoggerService::error('Erro ao registrar pagamento', [
            'usuario'   => $body['nome'] ?? 'unknown',
            'exception' => $e->getMessage(),
        ]);
        MetricsService::incrementarRequisicaoHttp($method, $rota, 500);
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

// ── PATCH ───────────────────────────────────────────────────────────────────
if ($method === 'PATCH') {
    if (!$id) {
        MetricsService::incrementarRequisicaoHttp($method, $rota, 400);
        http_response_code(400);
        echo json_encode(['erro' => 'Informe o id na URL: ?id=1']);
        exit;
    }

    $statusValidos = ['pendente', 'aprovado', 'recusado'];
    if (!isset($body['status']) || !in_array($body['status'], $statusValidos, true)) {
        MetricsService::incrementarRequisicaoHttp($method, $rota, 400);
        http_response_code(400);
        echo json_encode(['erro' => 'Status inválido. Use: ' . implode(', ', $statusValidos)]);
        exit;
    }

    try {
        $stmt = $conn->prepare('UPDATE Pagamentos SET Status = :status WHERE id = :id');
        $stmt->execute([':status' => $body['status'], ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            MetricsService::incrementarRequisicaoHttp($method, $rota, 404);
            http_response_code(404);
            echo json_encode(['erro' => 'Pagamento não encontrado.']);
        } else {
            LoggerService::info('Status de pagamento atualizado', [
                'id'     => $id,
                'status' => $body['status'],
            ]);
            MetricsService::incrementarRequisicaoHttp($method, $rota, 200);
            echo json_encode(['mensagem' => "Status atualizado para '{$body['status']}'."]);
        }
    } catch (\Throwable $e) {
        LoggerService::error('Erro ao atualizar status', ['id' => $id, 'exception' => $e->getMessage()]);
        MetricsService::incrementarRequisicaoHttp($method, $rota, 500);
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

// ── DELETE ──────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) {
        MetricsService::incrementarRequisicaoHttp($method, $rota, 400);
        http_response_code(400);
        echo json_encode(['erro' => 'Informe o id na URL: ?id=1']);
        exit;
    }

    try {
        $stmt = $conn->prepare('DELETE FROM Pagamentos WHERE id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            MetricsService::incrementarRequisicaoHttp($method, $rota, 404);
            http_response_code(404);
            echo json_encode(['erro' => 'Pagamento não encontrado.']);
        } else {
            LoggerService::info('Pagamento removido', ['id' => $id]);
            MetricsService::incrementarRequisicaoHttp($method, $rota, 200);
            echo json_encode(['mensagem' => 'Pagamento removido.']);
        }
    } catch (\Throwable $e) {
        LoggerService::error('Erro ao remover pagamento', ['id' => $id, 'exception' => $e->getMessage()]);
        MetricsService::incrementarRequisicaoHttp($method, $rota, 500);
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

MetricsService::incrementarRequisicaoHttp($method, $rota, 405);
http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);
