<?php

namespace Cafeteria\CQRS\Queries;

use Cafeteria\CQRS\QueryInterface;

final class BuscarCarrinhoQuery implements QueryInterface
{
    public function __construct(
        public readonly string $nomeUsuario,
    ) {}
}
