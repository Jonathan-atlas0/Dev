<?php
session_start(); 
$host ="localhost";
$user= "root";
$password ="";
$bd ="Cafeteria";

$conn =new mysqli($host, $user, $password);
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $bd");
mysqli_select_db($conn, $bd);
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS tabela(Produto VARCHAR(30),
                                                        Valor decimal(10,2),
                                                        Imagem VARCHAR(255),
                                                        Nome VARCHAR (20))");
mysqli_query($conn,"CREATE TABLE IF NOT EXISTS Finalizado(Produto VARCHAR(30),
                                                        Valor decimal(10,2),
                                                        Imagem VARCHAR(255),
                                                        Nome VARCHAR (20))");
?>