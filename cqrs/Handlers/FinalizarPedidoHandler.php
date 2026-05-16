<?php

namespace Cafeteria\CQRS\Handlers;

use PDO;
use Cafeteria\Cache\RedisService;
use Cafeteria\CQRS\CommandInterface;
use Cafeteria\CQRS\CommandHandlerInterface;
use Cafeteria\CQRS\Commands\FinalizarPedidoCommand;

/**
 * Finaliza o pedido com transação e publica evento no Redis Pub/Sub
 * para que o painel Admin receba a notificação em tempo real via WebSocket.
 */
class FinalizarPedidoHandler implements CommandHandlerInterface
{
    private const CANAL_PEDIDOS = 'cafeteria:pedidos:novo';

    public function __construct(
        private readonly PDO          $conn,
        private readonly RedisService $redis,
    ) {}

    public function handle(CommandInterface $command): void
    {
        /** @var FinalizarPedidoCommand $command */
        $nome = $command->nomeUsuario;

        $this->conn->beginTransaction();

        try {
            // 1. Busca itens do carrinho
            $sel = $this->conn->prepare("SELECT * FROM tabela WHERE Nome = :nome");
            $sel->execute([':nome' => $nome]);
            $itens = $sel->fetchAll(PDO::FETCH_ASSOC);

            if (empty($itens)) {
                $this->conn->rollBack();
                throw new \RuntimeException("Carrinho vazio — nada a finalizar.");
            }

            // 2. Insere em Finalizado
            $ins = $this->conn->prepare(
                "INSERT INTO Finalizado (Produto, Valor, Imagem, Nome)
                 VALUES (:produto, :valor, :imagem, :nome)"
            );

            $total = 0.0;
            foreach ($itens as $item) {
                $ins->execute([
                    ':produto' => $item['produto'] ?? $item['Produto'],
                    ':valor'   => $item['valor']   ?? $item['Valor'],
                    ':imagem'  => $item['imagem']  ?? $item['Imagem'] ?? '',
                    ':nome'    => $item['nome']    ?? $item['Nome'],
                ]);
                $total += (float)($item['valor'] ?? $item['Valor']);
            }

            // 3. Limpa carrinho
            $del = $this->conn->prepare("DELETE FROM tabela WHERE Nome = :nome");
            $del->execute([':nome' => $nome]);

            $this->conn->commit();

            // 4. Invalida cache do carrinho
            $this->redis->invalidarCarrinho($nome);

            // 5. Publica evento no Redis Pub/Sub → servidor WebSocket propaga ao Admin
            $this->redis->publicarEvento(self::CANAL_PEDIDOS, [
                'tipo'     => 'novo_pedido',
                'usuario'  => $nome,
                'itens'    => count($itens),
                'total'    => round($total, 2),
                'horario'  => date('H:i:s'),
            ]);

        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
