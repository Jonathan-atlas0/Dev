<?php

namespace Cafeteria\CQRS\Commands;

use Cafeteria\CQRS\CommandInterface;

/**
 * Representa a intenção de adicionar um produto ao carrinho.
 * Imutável por design — apenas leitura após construção.
 */
final class AdicionarAoCarrinhoCommand implements CommandInterface
{
    public function __construct(
        public readonly string $produto,
        public readonly float  $valor,
        public readonly string $imagem,
        public readonly string $nomeUsuario,
    ) {}
}
