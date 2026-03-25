<?php 
include_once("Conexao.php");
$nome= $_POST['nome'];
$_SESSION['nome'] =$nome; 
header("Location: index.html");
?>