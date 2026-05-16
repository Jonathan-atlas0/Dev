<?php

namespace Cafeteria\CQRS\Handlers;

use PDO;
use Cafeteria\Cache\RedisService;
use Cafeteria\CQRS\QueryInterface;
use Cafeteria\CQRS\QueryHandlerInterface;
use Cafeteria\CQRS\Queries\BuscarCarrinhoQuery;

/**
 * Cache-aside: tenta Redis primeiro, se miss vai ao banco e popula o cache.
 */
class BuscarCarrinhoHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly PDO          $conn,
        private readonly RedisService $redis,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        /** @var BuscarCarrinhoQuery $query */
        $nome = $query->nomeUsuario;

        // 1. Cache hit?
        $cached = $this->redis->getCarrinho($nome);
        if ($cached !== null) {
            return $cached;
        }

        // 2. Cache miss → banco
        $stmt = $this->conn->prepare(
            "SELECT id,
                    Produto AS produto,
                    Valor   AS valor,
                    Imagem  AS imagem,
                    Nome    AS nome
             FROM tabela
             WHERE Nome = :nome
             ORDER BY id"
        );
        $stmt->execute([':nome' => $nome]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Popula cache
        $this->redis->setCarrinho($nome, $itens);

        return $itens;
    }
}
