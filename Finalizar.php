<?php
include_once("Conexao.php");
$nome =$_SESSION['nome'];
 if (!isset($nome)) {
    mysqli_query($conn, "DELETE FROM tabela WHERE Nome='$nome'");
    mysqli_query($conn, "DELETE FROM Finalizado WHERE Nome='$nome'");
    header("Location: Login.php");
    exit;
}

$dados = mysqli_query($conn,"SELECT * FROM tabela WHERE Nome='$nome' ");
while($tabela = mysqli_fetch_assoc($dados)){
$insert = mysqli_query($conn, "INSERT  INTO Finalizado(Produto, Valor, Nome) 
                                VALUES('{$tabela['Produto']}','{$tabela['Valor']}','{$tabela['Nome']}')");
}
$finalizado = mysqli_query($conn,"SELECT * FROM Finalizado");
while($tabela2 =mysqli_fetch_assoc($finalizado)){
    echo ($tabela2['Produto']);
    echo ($tabela2['Valor']);
    echo ($tabela2['Nome']);
    echo "<br></br>";}
if($nome=='admin' || $nome=='Admin'){
    echo "Modo admin";
}
else{
    $_SESSION['mensagem'] = "Pedido realizado com sucesso ☕😋";
    mysqli_query($conn,"DELETE FROM tabela WHERE Nome='$nome'");
    header("Location: carrinho.php");
}


?>