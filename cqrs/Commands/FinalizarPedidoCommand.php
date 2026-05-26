<?php

namespace Cafeteria\CQRS\Commands;

use Cafeteria\CQRS\CommandInterface;

final class FinalizarPedidoCommand implements CommandInterface
{
    public function __construct(
        public readonly string $nomeUsuario,
    ) {}
}
