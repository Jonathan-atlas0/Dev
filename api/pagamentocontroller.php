<?php

/**
 * API de Pagamentos — /api/pagamentocontroller.php
 *
 * ROTAS:
 *
 *  GET  /api/pagamentocontroller.php                        → lista todos os pagamentos (admin)
 *  GET  /api/pagamentocontroller.php?nome=Joao              → pagamentos do usuário
 *  GET  /api/pagamentocontroller.php?nome=Joao&status=aprovado → filtro por status
 *
 *  POST /api/pagamentocontroller.php
 *       Body: { "nome": "Joao", "metodo": "pix", "valor": 45.90 }
 *       → registra pagamento com status 'pendente'
 *
 *  PATCH /api/pagamentocontroller.php?id=1
 *        Body: { "status": "aprovado" }   ou   { "status": "recusado" }
 *        → atualiza status do pagamento (uso admin)
 *
 *  DELETE /api/pagamentocontroller.php?id=1
 *         → remove pagamento (uso admin)
 */

declare(strict_types=1);

require_once __DIR__ . '/../Conexao.php';
require_once __DIR__ . '/../bootstrap.php';

use Cafeteria\CQRS\Commands\RegistrarPagamentoCommand;
use Cafeteria\CQRS\Queries\BuscarPagamentosQuery;
use Cafeteria\CQRS\Handlers\RegistrarPagamentoHandler;
use Cafeteria\CQRS\Handlers\BuscarPagamentosHandler;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Registra handlers de pagamento no bus
$commandBus->register(
    RegistrarPagamentoCommand::class,
    new RegistrarPagamentoHandler($conn, $redis)
);

$queryBus->register(
    BuscarPagamentosQuery::class,
    new BuscarPagamentosHandler($conn)
);

$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$nome   = $_GET['nome']   ?? null;
$status = $_GET['status'] ?? null;
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

// ── GET — Listar pagamentos ──────────────────────────────────────────────────
if ($method === 'GET') {
    try {
        $pagamentos = $queryBus->dispatch(new BuscarPagamentosQuery(
            nomeUsuario: $nome,
            status:      $status,
        ));
        echo json_encode($pagamentos);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

// ── POST — Registrar pagamento ───────────────────────────────────────────────
if ($method === 'POST') {
    $campos = ['nome', 'metodo', 'valor'];
    foreach ($campos as $campo) {
        if (!isset($body[$campo])) {
            http_response_code(400);
            echo json_encode(['erro' => "Campo obrigatório ausente: $campo"]);
            exit;
        }
    }

    $metodosValidos = ['pix', 'cartao', 'dinheiro'];
    if (!in_array($body['metodo'], $metodosValidos, true)) {
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
        http_response_code(201);
        echo json_encode(['mensagem' => 'Pagamento registrado com sucesso.', 'status' => 'pendente']);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

// ── PATCH — Atualizar status ─────────────────────────────────────────────────
if ($method === 'PATCH') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['erro' => 'Informe o id na URL: ?id=1']);
        exit;
    }

    $statusValidos = ['pendente', 'aprovado', 'recusado'];
    if (!isset($body['status']) || !in_array($body['status'], $statusValidos, true)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Status inválido. Use: ' . implode(', ', $statusValidos)]);
        exit;
    }

    try {
        $stmt = $conn->prepare('UPDATE Pagamentos SET Status = :status WHERE id = :id');
        $stmt->execute([':status' => $body['status'], ':id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pagamento não encontrado.']);
        } else {
            echo json_encode(['mensagem' => "Status atualizado para '{$body['status']}'."]);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

// ── DELETE — Remover pagamento ───────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) {
        http_response_code(400);
        echo json_encode(['erro' => 'Informe o id na URL: ?id=1']);
        exit;
    }

    try {
        $stmt = $conn->prepare('DELETE FROM Pagamentos WHERE id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['erro' => 'Pagamento não encontrado.']);
        } else {
            echo json_encode(['mensagem' => 'Pagamento removido.']);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);
