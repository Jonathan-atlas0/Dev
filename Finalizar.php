<?php
/**
 * Finalizar.php — refatorado com CQRS
 * Antes: SQL de SELECT + INSERT + DELETE misturado aqui.
 * Agora: um único Command com transação no Handler.
 */

session_start();
include_once("Conexao.php");
require_once("bootstrap.php");

use Cafeteria\CQRS\Commands\FinalizarPedidoCommand;
use Cafeteria\CQRS\Queries\BuscarPedidosFinalizadosQuery;

$nome = $_SESSION['nome'] ?? null;
if (!$nome) {
    header("Location: Login.php");
    exit;
}

// Modo Admin: exibe todos os pedidos finalizados (leitura pura via Query)
if (strtolower($nome) === 'admin') {
    $pedidos = $queryBus->dispatch(new BuscarPedidosFinalizadosQuery());
    echo "<h2>Painel Admin — Pedidos Finalizados</h2><ul>";
    foreach ($pedidos as $p) {
        echo "<li>{$p['nome']} — {$p['produto']} — R$ " . number_format($p['valor'], 2, ',', '.') . "</li>";
    }
    echo "</ul>";
    exit;
}

// Modo usuário: finaliza o pedido via Command
try {
    $commandBus->dispatch(new FinalizarPedidoCommand(nomeUsuario: $nome));
    $_SESSION['mensagem'] = "Pedido realizado com sucesso ☕😋";
} catch (\Throwable $e) {
    $_SESSION['mensagem'] = "Erro ao finalizar: " . $e->getMessage();
}

header("Location: carrinho.php");
exit;
