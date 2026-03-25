<?php
include_once("Conexao.php");
$nome = $_SESSION['nome'];
$stmt = $conn->prepare("DELETE FROM tabela WHERE Nome = :nome");
$stmt->execute([':nome' => $nome]);
header("Location: carrinho.php");
?>