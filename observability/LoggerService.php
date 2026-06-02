<?php

namespace Cafeteria\Observability;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\LogRecord;

/**
 * LoggerService
 * ─────────────
 * Equivalente ao Serilog (.NET) com JsonFormatter + Enrichers.
 *
 * Gera logs estruturados em JSON com:
 *  - CorrelationId por request  (UidProcessor → equivale ao Enricher do Serilog)
 *  - IP, URL e método HTTP      (WebProcessor → equivale ao HttpContextEnricher)
 *  - Arquivo + linha do código  (IntrospectionProcessor)
 *  - Environment e AppName nos campos extras
 *
 * Handlers (equivalente aos Sinks do Serilog):
 *  - StreamHandler  → stderr (console Docker / stdout do container)
 *  - RotatingFileHandler → logs/cafeteria-YYYY-MM-DD.log (arquivo rotativo)
 */
class LoggerService
{
    private static ?Logger $instance = null;

    public static function get(): Logger
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $logger = new Logger('cafeteria');

        // ── Processors (equivalente aos Enrichers do Serilog) ──────────────
        $logger->pushProcessor(new UidProcessor(24));      // CorrelationId único por request
        $logger->pushProcessor(new WebProcessor());         // IP, URL, método HTTP, referrer
        $logger->pushProcessor(new IntrospectionProcessor()); // arquivo e linha do código

        // Processor customizado — adiciona contexto do ambiente (Monolog v3: LogRecord)
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(extra: array_merge($record->extra, [
                'app'         => 'cafeteria',
                'environment' => getenv('APP_ENV') ?: 'production',
                'php_version' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            ]));
        });

        // ── Formatador JSON (equivalente ao JsonFormatter do Serilog) ──────
        $jsonFormatter = new JsonFormatter();

        // ── Handler 1: stderr → visível no `docker logs cafeteria_app` ──────
        $consoleHandler = new StreamHandler('php://stderr', Logger::DEBUG);
        $consoleHandler->setFormatter($jsonFormatter);
        $logger->pushHandler($consoleHandler);

        // ── Handler 2: arquivo rotativo diário ───────────────────────────────
        $logsDir = __DIR__ . '/../logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        $fileHandler = new RotatingFileHandler(
            $logsDir . '/cafeteria.log',
            maxFiles: 7,       // mantém 7 dias de logs
            level: Logger::INFO
        );
        $fileHandler->setFormatter($jsonFormatter);
        $logger->pushHandler($fileHandler);

        self::$instance = $logger;
        return $logger;
    }

    // ── Atalhos estáticos (mesma API do Serilog) ──────────────────────────

    public static function info(string $message, array $context = []): void
    {
        self::get()->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::get()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::get()->error($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::get()->debug($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::get()->critical($message, $context);
    }
}
