<?php
/**
 * Delete.php — refatorado com CQRS
 */

session_start();
include_once("Conexao.php");
require_once("bootstrap.php");

use Cafeteria\CQRS\Commands\LimparCarrinhoCommand;

$nome = $_SESSION['nome'] ?? null;
if (!$nome) {
    header("Location: Login.php");
    exit;
}

$commandBus->dispatch(new LimparCarrinhoCommand(nomeUsuario: $nome));

header("Location: carrinho.php");
exit;
