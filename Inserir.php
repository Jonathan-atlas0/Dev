<?php
include_once("Conexao.php");
$produto = $_POST['produto'];
$valor   = $_POST['valor'];
$imagem  = $_POST['imagem'];
$nome    = $_SESSION['nome'];
if (!isset($nome)) { header("Location: Login.php"); exit; }
$stmt = $conn->prepare("INSERT INTO tabela(Produto, Valor, Imagem, Nome) VALUES(:produto, :valor, :imagem, :nome)");
$ok = $stmt->execute([':produto'=>$produto,':valor'=>$valor,':imagem'=>$imagem,':nome'=>$nome]);
$_SESSION['mensagem'] = $ok ? "✅ Pedido adicionado ao carrinho!" : "Erro ao adicionar pedido.";
header("Location: menu.php"); exit;
?>
 