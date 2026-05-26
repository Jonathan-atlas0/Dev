<?php

namespace Cafeteria\Observability;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * ResilienceService
 * ─────────────────
 * Equivalente ao Polly v8 (.NET) integrado ao IHttpClientFactory.
 *
 * Implementa os três padrões de resiliência da Aula 11:
 *
 *  1. RETRY com backoff exponencial + jitter
 *     → equivale ao AddRetry(options => options.UseJitter = true) do Polly
 *
 *  2. CIRCUIT BREAKER com estados Closed/Open/Half-Open
 *     → estado armazenado no Redis (vs memória no Polly) — correto para multi-container
 *     → equivale ao AddCircuitBreaker() do Polly v8
 *
 *  3. TIMEOUT por requisição
 *     → equivale ao AddTimeout(TimeSpan.FromSeconds(5)) do Polly
 *
 * Diferença arquitetural:
 *   - Polly v8: estado do Circuit Breaker em memória do processo .NET
 *   - PHP:      estado no Redis → todos os containers/workers compartilham o mesmo circuito
 *               → mais correto em ambientes com múltiplas réplicas
 */
class ResilienceService
{
    // ── Configuração do Circuit Breaker ───────────────────────────────────
    private const CB_THRESHOLD       = 5;    // falhas para abrir o circuito
    private const CB_OPEN_SECONDS    = 30;   // tempo com circuito aberto (s)
    private const CB_HALF_OPEN_MAX   = 1;    // tentativas em Half-Open

    private static ?\Redis $redis = null;

    private static function redis(): ?\Redis
    {
        if (self::$redis !== null) {
            return self::$redis;
        }

        try {
            $r = new \Redis();
            $r->connect(
                getenv('REDIS_HOST') ?: 'redis',
                (int)(getenv('REDIS_PORT') ?: 6379),
                2.0
            );
            self::$redis = $r;
        } catch (\Throwable) {
            self::$redis = null;
        }

        return self::$redis;
    }

    // ─────────────────────────────────────────────────────────────────────
    // CIRCUIT BREAKER — estado no Redis
    // ─────────────────────────────────────────────────────────────────────

    private static function cbKey(string $servico): string
    {
        return "cafeteria:circuit_breaker:{$servico}";
    }

    public static function circuitoAberto(string $servico): bool
    {
        $redis = self::redis();
        if ($redis === null) {
            return false; // sem Redis, não bloqueia
        }

        $raw = $redis->get(self::cbKey($servico));
        if (!$raw) {
            return false;
        }

        $estado = json_decode($raw, true);

        // Estado OPEN — verifica se já passou o tempo de espera
        if ($estado['estado'] === 'open') {
            if (time() < $estado['aberto_ate']) {
                return true; // ainda aberto
            }

            // Transição OPEN → HALF-OPEN
            $estado['estado']         = 'half-open';
            $estado['tentativas_ho']  = 0;
            $redis->setEx(self::cbKey($servico), self::CB_OPEN_SECONDS * 2, json_encode($estado));

            LoggerService::warning('Circuit Breaker transitioning to Half-Open', [
                'service' => $servico,
            ]);
        }

        return false;
    }

    public static function registrarFalha(string $servico): void
    {
        $redis = self::redis();
        if ($redis === null) {
            return;
        }

        $key   = self::cbKey($servico);
        $raw   = $redis->get($key);
        $estado = $raw ? json_decode($raw, true) : [
            'estado'        => 'closed',
            'falhas'        => 0,
            'aberto_ate'    => 0,
            'tentativas_ho' => 0,
        ];

        $estado['falhas']++;

        if ($estado['falhas'] >= self::CB_THRESHOLD) {
            $estado['estado']     = 'open';
            $estado['aberto_ate'] = time() + self::CB_OPEN_SECONDS;

            LoggerService::error('Circuit Breaker OPENED', [
                'service'    => $servico,
                'failures'   => $estado['falhas'],
                'open_until' => date('H:i:s', $estado['aberto_ate']),
            ]);

            MetricsService::incrementarErroDependencia($servico);
        }

        $redis->setEx($key, self::CB_OPEN_SECONDS * 4, json_encode($estado));
    }

    public static function registrarSucesso(string $servico): void
    {
        $redis = self::redis();
        if ($redis === null) {
            return;
        }

        $key = self::cbKey($servico);
        $raw = $redis->get($key);

        if ($raw) {
            $estado = json_decode($raw, true);
            if ($estado['estado'] === 'half-open' || $estado['estado'] === 'open') {
                LoggerService::info('Circuit Breaker CLOSED (recovered)', [
                    'service' => $servico,
                ]);
            }
        }

        // Reset — circuito fechado
        $redis->del($key);
    }

    // ─────────────────────────────────────────────────────────────────────
    // GUZZLE CLIENT com Retry + Timeout
    // Equivalente ao .AddResilienceHandler() do Polly v8
    // ─────────────────────────────────────────────────────────────────────

    public static function createHttpClient(
        string $baseUri,
        string $servico      = 'externo',
        int    $maxRetries   = 3,
        float  $timeoutSec   = 5.0,
        float  $connectTimeout = 2.0
    ): Client {
        $stack = HandlerStack::create();

        // ── Middleware de Retry com backoff exponencial + jitter ────────────
        // Equivalente ao AddRetry(o => { o.MaxRetryAttempts = 3; o.UseJitter(); })
        $stack->push(Middleware::retry(
            // Decide se faz retry
            function (
                int $tentativa,
                \GuzzleHttp\Psr7\Request $req,
                ?\Psr\Http\Message\ResponseInterface $resp,
                ?\Throwable $ex
            ) use ($maxRetries, $servico): bool {
                if ($tentativa >= $maxRetries) {
                    return false;
                }

                // Retry em falha de conexão ou respostas 503/429
                $deveRetentar = $ex instanceof ConnectException
                    || ($resp && in_array($resp->getStatusCode(), [503, 429, 502], true));

                if ($deveRetentar) {
                    LoggerService::warning('HTTP retry attempt', [
                        'service'   => $servico,
                        'attempt'   => $tentativa + 1,
                        'max'       => $maxRetries,
                        'reason'    => $ex?->getMessage() ?? "HTTP {$resp?->getStatusCode()}",
                    ]);
                }

                return $deveRetentar;
            },

            // Calcula delay — backoff exponencial com jitter
            // Equivalente ao UseJitter = true do Polly
            function (int $tentativa): int {
                $base  = (int)(500 * (2 ** $tentativa)); // 500ms, 1000ms, 2000ms...
                $jitter = random_int(0, 200);             // +0..200ms de jitter
                return $base + $jitter;
            }
        ));

        return new Client([
            'base_uri'        => $baseUri,
            'handler'         => $stack,
            'timeout'         => $timeoutSec,         // Timeout por requisição
            'connect_timeout' => $connectTimeout,     // Timeout TCP
            'http_errors'     => false,               // Não lança exceção em 4xx/5xx
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Wrapper com Circuit Breaker integrado
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Executa uma chamada HTTP com Circuit Breaker + Retry + Timeout.
     *
     * @param callable $chamada  fn(Client): ResponseInterface
     * @return \Psr\Http\Message\ResponseInterface|null  null = circuito aberto
     */
    public static function executarComResiliencia(
        string $servico,
        string $baseUri,
        callable $chamada
    ): ?\Psr\Http\Message\ResponseInterface {
        // Verifica Circuit Breaker antes de qualquer chamada
        if (self::circuitoAberto($servico)) {
            LoggerService::warning('Circuit Breaker is OPEN — call rejected', [
                'service' => $servico,
            ]);
            return null;
        }

        $cliente = self::createHttpClient($baseUri, $servico);

        try {
            $inicio   = microtime(true);
            $resposta = $chamada($cliente);
            $duracao  = microtime(true) - $inicio;

            $statusCode = $resposta->getStatusCode();

            if ($statusCode >= 500) {
                self::registrarFalha($servico);
                LoggerService::error('HTTP call failed (server error)', [
                    'service'     => $servico,
                    'status_code' => $statusCode,
                    'duration_ms' => round($duracao * 1000, 2),
                ]);
            } else {
                self::registrarSucesso($servico);
                LoggerService::info('HTTP call succeeded', [
                    'service'     => $servico,
                    'status_code' => $statusCode,
                    'duration_ms' => round($duracao * 1000, 2),
                ]);
            }

            MetricsService::incrementarRequisicaoHttp(
                metodo: 'POST',
                rota: $servico,
                statusCode: $statusCode
            );

            return $resposta;

        } catch (\Throwable $e) {
            self::registrarFalha($servico);
            LoggerService::error('HTTP call exception', [
                'service'   => $servico,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
