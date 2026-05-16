<?php

namespace Cafeteria\CQRS\Commands;

use Cafeteria\CQRS\CommandInterface;

final class LimparCarrinhoCommand implements CommandInterface
{
    public function __construct(
        public readonly string $nomeUsuario,
    ) {}
}
