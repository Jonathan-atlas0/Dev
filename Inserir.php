<?php 
include_once("Conexao.php");
 $produto =$_POST['produto'];
 $valor = $_POST['valor'];
 $imagem =$_POST['imagem'];
 $nome = $_SESSION['nome'];
 if(!isset($nome)){
    header("Location: Login.php");
    exit;
 }
$insert = mysqli_query($conn, "INSERT  INTO tabela(Produto, Valor, Imagem, Nome) VALUES('$produto', '$valor', '$imagem','$nome')");
if ($insert) {
    $_SESSION['mensagem'] = "✅ Pedido adicionado ao carrinho!";
    header("Location: menu.php");
} else {
    $_SESSION['mensagem'] = "Erro";
    header("Location: menu.php");
}

exit;

?>