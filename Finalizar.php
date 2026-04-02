<?php
include_once("Conexao.php");
require_once("models/EstoqueModel.php");

$nome = $_SESSION['nome'];

if (!isset($nome)) {
    header("Location: Login.php");
    exit;
}

$estoque = new EstoqueModel();

$dados = mysqli_query($conn, "SELECT * FROM tabela WHERE Nome='$nome'");

while ($tabela = mysqli_fetch_assoc($dados)) {
    
    $estoque->decrementar($tabela['Produto']);

    mysqli_query($conn, "INSERT INTO Finalizado(Produto, Valor, Nome) 
                         VALUES('{$tabela['Produto']}','{$tabela['Valor']}','{$tabela['Nome']}')");
}

if ($nome == 'admin' || $nome == 'Admin') {
    echo "Modo admin";
} else {
    $_SESSION['mensagem'] = "Pedido realizado com sucesso ☕😋";
    mysqli_query($conn, "DELETE FROM tabela WHERE Nome='$nome'");
    header("Location: carrinho.php");
}
?>