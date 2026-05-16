<?php

namespace Cafeteria\CQRS\Handlers;

use PDO;
use Cafeteria\CQRS\QueryInterface;
use Cafeteria\CQRS\QueryHandlerInterface;
use Cafeteria\CQRS\Queries\BuscarPedidosFinalizadosQuery;

class BuscarPedidosFinalizadosHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PDO $conn,
    ) {}

    public function handle(QueryInterface $query): array
    {
        /** @var BuscarPedidosFinalizadosQuery $query */
        if ($query->nomeUsuario) {
            $stmt = $this->conn->prepare(
                "SELECT * FROM Finalizado WHERE Nome = :nome ORDER BY id DESC"
            );
            $stmt->execute([':nome' => $query->nomeUsuario]);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM Finalizado ORDER BY id DESC");
            $stmt->execute();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
