<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
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
?>