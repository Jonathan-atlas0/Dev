<?php

namespace Cafeteria\Observability;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis as RedisAdapter;
use Prometheus\Storage\InMemory;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;

/**
 * MetricsService
 * ──────────────
 * Equivalente ao prometheus-net (.NET) com Counter, Gauge e Histogram.
 *
 * Diferença arquitetural (PHP stateless vs .NET stateful):
 *   - prometheus-net mantém métricas na memória do processo .NET.
 *   - Em PHP cada request é um processo novo — o Redis é obrigatório
 *     como storage compartilhado para acumular valores entre requests.
 *
 * Endpoint /metrics.php expõe no formato Prometheus (OpenMetrics),
 * consumido pelo Prometheus a cada 15s conforme prometheus.yml.
 */
class MetricsService
{
    private static ?CollectorRegistry $registry = null;

    private static function registry(): CollectorRegistry
    {
        if (self::$registry !== null) {
            return self::$registry;
        }

        try {
            // Storage Redis — necessário por PHP ser stateless
            $adapter = new RedisAdapter([
                'host'    => getenv('REDIS_HOST') ?: 'redis',
                'port'    => (int)(getenv('REDIS_PORT') ?: 6379),
                'timeout' => 2,
            ]);
            self::$registry = new CollectorRegistry($adapter);
        } catch (\Throwable) {
            // Fallback InMemory se Redis indisponível (perde dados entre requests)
            self::$registry = new CollectorRegistry(new InMemory());
        }

        return self::$registry;
    }

    // ── COUNTER: pedidos criados ──────────────────────────────────────────
    // Equivalente ao Metrics.CreateCounter("pedidos_criados_total") do .NET

    public static function incrementarPedidosCriados(string $status = 'Criado'): void
    {
        try {
            $counter = self::registry()->getOrRegisterCounter(
                'cafeteria',
                'pedidos_criados_total',
                'Total de pedidos criados por status',
                ['status']
            );
            $counter->inc([$status]);
        } catch (\Throwable) {
            // Métricas nunca devem quebrar a aplicação
        }
    }

    // ── COUNTER: requisições HTTP ────────────────────────────────────────
    // Equivalente ao UseHttpMetrics() do prometheus-net

    public static function incrementarRequisicaoHttp(string $metodo, string $rota, int $statusCode): void
    {
        try {
            $counter = self::registry()->getOrRegisterCounter(
                'cafeteria',
                'http_requests_total',
                'Total de requisições HTTP por método, rota e status code',
                ['method', 'route', 'status_code']
            );
            $counter->inc([$metodo, $rota, (string)$statusCode]);
        } catch (\Throwable) {}
    }

    // ── COUNTER: erros de dependência ────────────────────────────────────

    public static function incrementarErroDependencia(string $servico): void
    {
        try {
            $counter = self::registry()->getOrRegisterCounter(
                'cafeteria',
                'dependency_errors_total',
                'Total de erros em dependências externas (db, redis)',
                ['service']
            );
            $counter->inc([$servico]);
        } catch (\Throwable) {}
    }

    // ── GAUGE: pedidos ativos no momento ─────────────────────────────────
    // Equivalente ao Metrics.CreateGauge("pedidos_ativos") do .NET

    public static function setPedidosAtivos(int $quantidade): void
    {
        try {
            $gauge = self::registry()->getOrRegisterGauge(
                'cafeteria',
                'pedidos_ativos',
                'Número de pedidos ativos no carrinho no momento'
            );
            $gauge->set($quantidade);
        } catch (\Throwable) {}
    }

    // ── HISTOGRAM: latência de criação de pedido ─────────────────────────
    // Equivalente ao Metrics.CreateHistogram("pedido_criacao_duracao_segundos") do .NET

    public static function registrarLatenciaPedido(float $duracaoSegundos): void
    {
        try {
            $histogram = self::registry()->getOrRegisterHistogram(
                'cafeteria',
                'pedido_criacao_duracao_segundos',
                'Latência de criação de pedido em segundos',
                [],
                [0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0]
            );
            $histogram->observe($duracaoSegundos);
        } catch (\Throwable) {}
    }

    // ── HISTOGRAM: latência de queries SQL ───────────────────────────────

    public static function registrarLatenciaDB(float $duracaoSegundos, string $operacao): void
    {
        try {
            $histogram = self::registry()->getOrRegisterHistogram(
                'cafeteria',
                'db_query_duracao_segundos',
                'Latência de queries no PostgreSQL por operação',
                ['operation'],
                [0.01, 0.05, 0.1, 0.5, 1.0]
            );
            $histogram->observe($duracaoSegundos, [$operacao]);
        } catch (\Throwable) {}
    }

    // ── Renderiza o endpoint /metrics ────────────────────────────────────
    // Consumido pelo Prometheus a cada 15s (prometheus.yml)

    public static function render(): string
    {
        $renderer = new \Prometheus\RenderTextFormat();
        return $renderer->render(self::registry()->getMetricFamilySamples());
    }

    public static function mimeType(): string
    {
        return \Prometheus\RenderTextFormat::MIME_TYPE;
    }
}
