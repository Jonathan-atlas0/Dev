<?php
session_start();
 
$host = getenv("DB_HOST") ?: "db";
$user = getenv("DB_USER") ?: "cafeteria";
$password = getenv("DB_PASS") ?: "cafeteria123";
$bd   = getenv("DB_NAME") ?: "Cafeteria";
 
try {
    $conn = new PDO("pgsql:host=$host;dbname=$bd", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
    // Cria tabela de carrinho
    $conn->exec("CREATE TABLE IF NOT EXISTS tabela (
        id      SERIAL PRIMARY KEY,
        Produto VARCHAR(30),
        Valor   DECIMAL(10,2),
        Imagem  VARCHAR(255),
        Nome    VARCHAR(20)
    )");
 
    // Cria tabela de pedidos finalizados
    $conn->exec("CREATE TABLE IF NOT EXISTS Finalizado (
        id      SERIAL PRIMARY KEY,
        Produto VARCHAR(30),
        Valor   DECIMAL(10,2),
        Imagem  VARCHAR(255),
        Nome    VARCHAR(20)
    )");
 
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>
 