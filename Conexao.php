<?php
session_start();
$host = "db";
$user = "root";
$password = "root";
$bd = "cafeteria";

$conn = new mysqli($host, $user, $password, $bd);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tabela(
    Produto VARCHAR(30),
    Valor DECIMAL(10,2),
    Imagem VARCHAR(255),
    Nome VARCHAR(20)
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS Finalizado(
    Produto VARCHAR(30),
    Valor DECIMAL(10,2),
    Imagem VARCHAR(255),
    Nome VARCHAR(20)
)");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS Estoque(
    Produto VARCHAR(30),
    Valor DECIMAL(10,2),
    Imagem VARCHAR(255),
    Quantidade INT
)");

$count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM Estoque"));
if ($count['total'] == 0) {
    mysqli_query($conn, "INSERT INTO Estoque (Produto, Valor, Imagem, Quantidade) VALUES
        ('Capuccino', 15.99, './img/menu-1.png', 10),
        ('Mocha', 12.59, './img/menu-2.png', 10),
        ('Macchiato', 16.99, './img/menu-3.png', 10),
        ('café Expresso', 10.00, './img/menu-4.png', 10),
        ('Leite e Caramelo', 14.99, './img/menu-5.png', 10),
        ('café preto', 18.99, './img/menu-6.png', 10)
    ");
}
?>