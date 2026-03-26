<?php
include_once("Conexao.php");
$nome =$_SESSION['nome'];
$del =mysqli_query($conn,"DELETE FROM tabela WHERE Nome='$nome' ");
header("Location: carrinho.php");
?>