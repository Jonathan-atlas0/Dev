<?php

namespace Cafeteria\CQRS\Handlers;

use PDO;
use Cafeteria\Cache\RedisService;
use Cafeteria\CQRS\CommandInterface;
use Cafeteria\CQRS\CommandHandlerInterface;
use Cafeteria\CQRS\Commands\FinalizarPedidoCommand;
use Cafeteria\Observability\LoggerService;
use Cafeteria\Observability\MetricsService;

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
        $nome   = $command->nomeUsuario;
        $inicio = microtime(true);

        LoggerService::info('Finalizando pedido', [
            'usuario'   => $nome,
            'timestamp' => date('c'),
        ]);

        $this->conn->beginTransaction();

        try {
            $sel = $this->conn->prepare("SELECT * FROM tabela WHERE Nome = :nome");
            $sel->execute([':nome' => $nome]);
            $itens = $sel->fetchAll(PDO::FETCH_ASSOC);

            if (empty($itens)) {
                $this->conn->rollBack();
                LoggerService::warning('Tentativa de finalizar carrinho vazio', ['usuario' => $nome]);
                throw new \RuntimeException("Carrinho vazio — nada a finalizar.");
            }

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

            $del = $this->conn->prepare("DELETE FROM tabela WHERE Nome = :nome");
            $del->execute([':nome' => $nome]);

            $this->conn->commit();

            // ── Baixa no estoque ──────────────────────────────────────────
            // Feito fora da transação principal para não bloquear o pedido
            // caso o produto não exista no estoque ainda.
            foreach ($itens as $item) {
                $produto = $item['produto'] ?? $item['Produto'];
                try {
                    $patch = $this->conn->prepare(
                        "UPDATE Estoque SET quantidade = quantidade - 1
                         WHERE produto = :produto AND quantidade > 0"
                    );
                    $patch->execute([':produto' => $produto]);

                    if ($patch->rowCount() === 0) {
                        LoggerService::warning('Produto não encontrado ou sem estoque', [
                            'produto' => $produto,
                        ]);
                    }
                } catch (\Throwable $e) {
                    // Loga mas não cancela o pedido por falha de estoque
                    LoggerService::error('Falha ao dar baixa no estoque', [
                        'produto'   => $produto,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            $duracao = microtime(true) - $inicio;

            MetricsService::incrementarPedidosCriados('Finalizado');
            MetricsService::registrarLatenciaPedido($duracao);

            LoggerService::info('Pedido finalizado com sucesso', [
                'usuario'    => $nome,
                'itens'      => count($itens),
                'total'      => round($total, 2),
                'duracao_ms' => round($duracao * 1000, 2),
            ]);

            $this->redis->invalidarCarrinho($nome);

            $this->redis->publicarEvento(self::CANAL_PEDIDOS, [
                'tipo'    => 'novo_pedido',
                'usuario' => $nome,
                'itens'   => count($itens),
                'total'   => round($total, 2),
                'horario' => date('H:i:s'),
            ]);

        } catch (\Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();

            LoggerService::error('Erro ao finalizar pedido', [
                'usuario'   => $nome,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            MetricsService::incrementarErroDependencia('postgres');
            throw $e;
        }
    }
}