<?php

namespace Cafeteria\CQRS\Commands;

use Cafeteria\CQRS\CommandInterface;

/**
 * Representa a intenção de registrar um pagamento de pedido.
 * Imutável por design — apenas leitura após construção.
 */
final class RegistrarPagamentoCommand implements CommandInterface
{
    public function __construct(
        public readonly string $nomeUsuario,
        public readonly string $metodoPagamento, // 'pix' | 'cartao' | 'dinheiro'
        public readonly float  $valor,
    ) {}
}
