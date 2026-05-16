<?php

namespace Cafeteria\CQRS\Handlers;

use PDO;
use Cafeteria\CQRS\CommandInterface;
use Cafeteria\CQRS\CommandHandlerInterface;
use Cafeteria\CQRS\Commands\AdicionarAoCarrinhoCommand;

/**
 * Toda a lógica de inserção no carrinho fica aqui.
 * O arquivo Inserir.php apenas monta o Command e despacha — sem SQL.
 */
class AdicionarAoCarrinhoHandler implements CommandHandlerInterface
{
    public function __construct(private readonly PDO $conn) {}

    public function handle(CommandInterface $command): void
    {
        /** @var AdicionarAoCarrinhoCommand $command */
        $stmt = $this->conn->prepare(
            "INSERT INTO tabela (Produto, Valor, Imagem, Nome)
             VALUES (:produto, :valor, :imagem, :nome)"
        );

        $ok = $stmt->execute([
            ':produto' => $command->produto,
            ':valor'   => $command->valor,
            ':imagem'  => $command->imagem,
            ':nome'    => $command->nomeUsuario,
        ]);

        if (!$ok) {
            throw new \RuntimeException("Falha ao inserir item no carrinho.");
        }
    }
}
