<?php

namespace Cafeteria\CQRS\Handlers;

use PDO;
use Cafeteria\Cache\RedisService;
use Cafeteria\CQRS\CommandInterface;
use Cafeteria\CQRS\CommandHandlerInterface;
use Cafeteria\CQRS\Commands\RegistrarPagamentoCommand;

/**
 * Registra o pagamento na tabela Pagamentos com transação.
 * Publica evento no Redis para notificação em tempo real.
 */
class RegistrarPagamentoHandler implements CommandHandlerInterface
{
    private const CANAL_PAGAMENTOS = 'cafeteria:pagamentos:novo';

    public function __construct(
        private readonly PDO          $conn,
        private readonly RedisService $redis,
    ) {}

    public function handle(CommandInterface $command): void
    {
        /** @var RegistrarPagamentoCommand $command */

        $this->conn->beginTransaction();

        try {
            // Cria tabela se não existir
            $this->conn->exec("CREATE TABLE IF NOT EXISTS Pagamentos (
                id               SERIAL PRIMARY KEY,
                Nome             VARCHAR(50)    NOT NULL,
                MetodoPagamento  VARCHAR(20)    NOT NULL,
                Valor            DECIMAL(10,2)  NOT NULL,
                Status           VARCHAR(20)    NOT NULL DEFAULT 'pendente',
                CriadoEm         TIMESTAMP      NOT NULL DEFAULT NOW()
            )");

            $stmt = $this->conn->prepare(
                "INSERT INTO Pagamentos (Nome, MetodoPagamento, Valor, Status)
                 VALUES (:nome, :metodo, :valor, 'pendente')"
            );

            $stmt->execute([
                ':nome'   => $command->nomeUsuario,
                ':metodo' => $command->metodoPagamento,
                ':valor'  => $command->valor,
            ]);

            $this->conn->commit();

            // Publica evento no Redis
            $this->redis->publicarEvento(self::CANAL_PAGAMENTOS, [
                'tipo'    => 'novo_pagamento',
                'usuario' => $command->nomeUsuario,
                'metodo'  => $command->metodoPagamento,
                'valor'   => $command->valor,
                'horario' => date('H:i:s'),
            ]);

        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
