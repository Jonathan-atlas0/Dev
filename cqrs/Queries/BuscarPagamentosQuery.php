<?php

namespace Cafeteria\CQRS\Queries;

use Cafeteria\CQRS\QueryInterface;

/**
 * Query para listar pagamentos.
 * nomeUsuario null = todos (admin); preenchido = apenas do usuário.
 */
final class BuscarPagamentosQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $nomeUsuario = null,
        public readonly ?string $status      = null, // 'pendente' | 'aprovado' | 'recusado'
    ) {}
}
