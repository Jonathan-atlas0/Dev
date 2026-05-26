<?php

namespace Cafeteria\CQRS\Bus;

use Cafeteria\CQRS\QueryInterface;
use Cafeteria\CQRS\QueryHandlerInterface;

/**
 * QueryBus: recebe uma Query e retorna dados do Handler correto.
 * Queries NUNCA modificam estado — apenas leem.
 */
class QueryBus
{
    /** @var array<string, QueryHandlerInterface> */
    private array $handlers = [];

    public function register(string $queryClass, QueryHandlerInterface $handler): void
    {
        $this->handlers[$queryClass] = $handler;
    }

    public function dispatch(QueryInterface $query): mixed
    {
        $class = get_class($query);

        if (!isset($this->handlers[$class])) {
            throw new \RuntimeException("Nenhum handler registrado para a query: $class");
        }

        return $this->handlers[$class]->handle($query);
    }
}
