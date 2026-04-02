<?php
// models/EstoqueModel.php

require_once __DIR__ . '/../Conexao.php';

class EstoqueModel {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function listarTodos(): array {
        $resultado = $this->conn->query("SELECT * FROM Estoque ORDER BY Produto ASC");
        $itens = [];
        while ($linha = $resultado->fetch_assoc()) {
            $itens[] = $linha;
        }
        return $itens;
    }

    public function buscarPorNome(string $produto) {
        $stmt = $this->conn->prepare("SELECT * FROM Estoque WHERE Produto = ?");
        $stmt->bind_param("s", $produto);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function inserir(string $produto, float $valor, string $imagem, int $quantidade): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO Estoque (Produto, Valor, Imagem, Quantidade) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("sdsi", $produto, $valor, $imagem, $quantidade);
        return $stmt->execute();
    }

    public function atualizarQuantidade(string $produto, int $quantidade): bool {
        $stmt = $this->conn->prepare(
            "UPDATE Estoque SET Quantidade = ? WHERE Produto = ?"
        );
        $stmt->bind_param("is", $quantidade, $produto);
        return $stmt->execute();
    }

    public function decrementar(string $produto): bool {
        $stmt = $this->conn->prepare(
            "UPDATE Estoque SET Quantidade = Quantidade - 1 WHERE Produto = ? AND Quantidade > 0"
        );
        $stmt->bind_param("s", $produto);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function deletar(string $produto): bool {
        $stmt = $this->conn->prepare("DELETE FROM Estoque WHERE Produto = ?");
        $stmt->bind_param("s", $produto);
        return $stmt->execute();
    }
}