<?php
include_once("Conexao.php");
echo "Session ID: " . session_id() . "<br>";
echo "Nome na sessão: " . ($_SESSION['nome'] ?? 'VAZIO') . "<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>