<?php
include_once("Conexao.php");
$nome = $_SESSION['nome'];
if (!isset($nome)) { header("Location: Login.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM tabela WHERE Nome = :nome");
$stmt->execute([':nome' => $nome]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ins = $conn->prepare("INSERT INTO Finalizado(Produto, Valor, Nome) VALUES(:produto, :valor, :nome)");
foreach ($itens as $item) {
    $ins->execute([':produto'=>$item['produto'],':valor'=>$item['valor'],':nome'=>$item['nome']]);
}

if (strtolower($nome) === 'admin') {
    $fin = $conn->query("SELECT * FROM Finalizado");
    while ($row = $fin->fetch(PDO::FETCH_ASSOC)) {
        echo $row['produto'] . " - R$ " . $row['valor'] . " - " . $row['nome'] . "<br>";
    }
    echo "Modo admin";
} else {
    $_SESSION['mensagem'] = "Pedido realizado com sucesso ☕😋";
    $del = $conn->prepare("DELETE FROM tabela WHERE Nome = :nome");
    $del->execute([':nome' => $nome]);
    header("Location: carrinho.php");
}
?>