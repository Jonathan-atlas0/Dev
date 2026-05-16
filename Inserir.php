<?php
/**
 * Inserir.php — refatorado com CQRS
 * Antes: SQL direto aqui.
 * Agora: monta o Command e despacha. Toda lógica fica no Handler.
 */

session_start();
include_once("Conexao.php");   // fornece $conn
require_once("bootstrap.php"); // fornece $commandBus

use Cafeteria\CQRS\Commands\AdicionarAoCarrinhoCommand;

$nome = $_SESSION['nome'] ?? null;
if (!$nome) {
    header("Location: Login.php");
    exit;
}

try {
    $command = new AdicionarAoCarrinhoCommand(
        produto:     $_POST['produto'],
        valor:       (float) $_POST['valor'],
        imagem:      $_POST['imagem'],
        nomeUsuario: $nome,
    );

    $commandBus->dispatch($command);
    $_SESSION['mensagem'] = "✅ Pedido adicionado ao carrinho!";

} catch (\Throwable $e) {
    $_SESSION['mensagem'] = "Erro ao adicionar pedido: " . $e->getMessage();
}

header("Location: menu.php");
exit;
