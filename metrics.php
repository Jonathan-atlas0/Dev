<?php

/**
 * metrics.php — Endpoint /metrics para Prometheus
 * ─────────────────────────────────────────────────
 * Equivalente ao app.UseMetricServer() do prometheus-net (.NET).
 *
 * Expõe métricas no formato Prometheus (OpenMetrics / text),
 * consumido pelo container Prometheus a cada 15s (prometheus.yml).
 *
 * Métricas disponíveis:
 *   cafeteria_pedidos_criados_total{status}          → Counter
 *   cafeteria_http_requests_total{method,route,code} → Counter
 *   cafeteria_dependency_errors_total{service}       → Counter
 *   cafeteria_pedidos_ativos                         → Gauge
 *   cafeteria_pedido_criacao_duracao_segundos         → Histogram
 *   cafeteria_db_query_duracao_segundos{operation}   → Histogram
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Cafeteria\Observability\MetricsService;

// Apenas Prometheus deve acessar este endpoint (proteção básica)
// Em produção, adicione autenticação ou restrinja por IP no nginx/Apache
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$remoteIp  = $_SERVER['REMOTE_ADDR'] ?? '';

header('Content-type: ' . MetricsService::mimeType());
echo MetricsService::render();
