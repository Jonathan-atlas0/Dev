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
 *
 * Se o Redis não estiver disponível, todas as operações são
 * silenciosamente ignoradas — o sistema funciona sem cache.
 */
class RedisService
{
    private ?\Redis $redis = null;

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

        try {
            $cliente = new \Redis();
            $cliente->connect($host, $port, 2.0); // timeout 2s
            $this->redis = $cliente;
        } catch (\Exception $e) {
            // Redis indisponível — sistema continua sem cache
            $this->redis = null;
        }
    }

    // ── MENU ──────────────────────────────────────────────────────────────

    public function getMenu(): ?array
    {
        if ($this->redis === null) return null;
        $raw = $this->redis->get(self::PREFIX_MENU);
        return $raw ? json_decode($raw, true) : null;
    }

    public function setMenu(array $produtos): void
    {
        if ($this->redis === null) return;
        $this->redis->setEx(self::PREFIX_MENU, self::TTL_MENU, json_encode($produtos));
    }

    public function invalidarMenu(): void
    {
        if ($this->redis === null) return;
        $this->redis->del(self::PREFIX_MENU);
    }

    // ── CARRINHO ──────────────────────────────────────────────────────────

    public function getCarrinho(string $nome): ?array
    {
        if ($this->redis === null) return null;
        $raw = $this->redis->get(self::PREFIX_CARRINHO . $nome);
        return $raw ? json_decode($raw, true) : null;
    }

    public function setCarrinho(string $nome, array $itens): void
    {
        if ($this->redis === null) return;
        $this->redis->setEx(
            self::PREFIX_CARRINHO . $nome,
            self::TTL_CARRINHO,
            json_encode($itens)
        );
    }

    public function invalidarCarrinho(string $nome): void
    {
        if ($this->redis === null) return;
        $this->redis->del(self::PREFIX_CARRINHO . $nome);
    }

    // ── PUB/SUB ───────────────────────────────────────────────────────────

    public function publicarEvento(string $canal, array $payload): void
    {
        if ($this->redis === null) return;
        $this->redis->publish($canal, json_encode($payload));
    }

    public function getRedis(): ?\Redis
    {
        return $this->redis;
    }
}