<?php

namespace Cafeteria\Cache;

/**
 * RedisService
 * ─────────────
 * Centraliza conexão e operações de cache com o Redis.
 * Usa a extensão phpredis (disponível no container).
 *
 * TTLs configurados:
 *   - Menu (produtos):  10 minutos  — raramente muda
 *   - Carrinho usuário: 5 minutos   — invalidado a cada escrita
 */
class RedisService
{
    private \Redis $redis;

    // Prefixos de chave para evitar colisões
    private const PREFIX_MENU     = 'cafeteria:menu';
    private const PREFIX_CARRINHO = 'cafeteria:carrinho:';

    // TTLs em segundos
    private const TTL_MENU     = 600; // 10 min
    private const TTL_CARRINHO = 300; //  5 min

    public function __construct()
    {
        $host = getenv('REDIS_HOST') ?: 'redis';
        $port = (int)(getenv('REDIS_PORT') ?: 6379);

        $this->redis = new \Redis();
        $this->redis->connect($host, $port, 2.0); // timeout 2s
    }

    // ── MENU ──────────────────────────────────────────────────────────────

    public function getMenu(): ?array
    {
        $raw = $this->redis->get(self::PREFIX_MENU);
        return $raw ? json_decode($raw, true) : null;
    }

    public function setMenu(array $produtos): void
    {
        $this->redis->setEx(self::PREFIX_MENU, self::TTL_MENU, json_encode($produtos));
    }

    public function invalidarMenu(): void
    {
        $this->redis->del(self::PREFIX_MENU);
    }

    // ── CARRINHO ──────────────────────────────────────────────────────────

    public function getCarrinho(string $nome): ?array
    {
        $raw = $this->redis->get(self::PREFIX_CARRINHO . $nome);
        return $raw ? json_decode($raw, true) : null;
    }

    public function setCarrinho(string $nome, array $itens): void
    {
        $this->redis->setEx(
            self::PREFIX_CARRINHO . $nome,
            self::TTL_CARRINHO,
            json_encode($itens)
        );
    }

    public function invalidarCarrinho(string $nome): void
    {
        $this->redis->del(self::PREFIX_CARRINHO . $nome);
    }

    // ── PUB/SUB ──────────────────────────────────────────────────────────

    /**
     * Publica um evento no canal Redis para o servidor WebSocket escutar.
     * Payload é serializado em JSON.
     */
    public function publicarEvento(string $canal, array $payload): void
    {
        $this->redis->publish($canal, json_encode($payload));
    }

    public function getRedis(): \Redis
    {
        return $this->redis;
    }
}
