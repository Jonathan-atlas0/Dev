<?php

namespace Cafeteria\CQRS\Handlers;

use PDO;
use Cafeteria\CQRS\QueryInterface;
use Cafeteria\CQRS\QueryHandlerInterface;
use Cafeteria\CQRS\Queries\BuscarPagamentosQuery;

/**
 * Leitura pura — sem efeito colateral.
 * Filtra por usuário e/ou status se informados.
 */
class BuscarPagamentosHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PDO $conn,
    ) {}

    public function handle(QueryInterface $query): array
    {
        /** @var BuscarPagamentosQuery $query */

        $where  = [];
        $params = [];

        if ($query->nomeUsuario !== null) {
            $where[]           = 'Nome = :nome';
            $params[':nome']   = $query->nomeUsuario;
        }

        if ($query->status !== null) {
            $where[]           = 'Status = :status';
            $params[':status'] = $query->status;
        }

        $sql = "SELECT id, Nome, MetodoPagamento, Valor, Status, CriadoEm
                FROM Pagamentos";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY CriadoEm DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
