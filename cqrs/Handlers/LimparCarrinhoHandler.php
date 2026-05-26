<?php

namespace Cafeteria\CQRS\Handlers;

use PDO;
use Cafeteria\Cache\RedisService;
use Cafeteria\CQRS\CommandInterface;
use Cafeteria\CQRS\CommandHandlerInterface;
use Cafeteria\CQRS\Commands\LimparCarrinhoCommand;

class LimparCarrinhoHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly PDO          $conn,
        private readonly RedisService $redis,
    ) {}

    public function handle(CommandInterface $command): void
    {
        /** @var LimparCarrinhoCommand $command */
        $stmt = $this->conn->prepare("DELETE FROM tabela WHERE Nome = :nome");
        $stmt->execute([':nome' => $command->nomeUsuario]);
        $this->redis->invalidarCarrinho($command->nomeUsuario);
    }
}
