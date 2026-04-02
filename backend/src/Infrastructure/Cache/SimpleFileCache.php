<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

/**
 * Cache simples baseado em arquivos.
 */
class SimpleFileCache
{
    private const CACHE_DIR = '/tmp/driverelite_cache';

    public function __construct()
    {
        if (!is_dir(self::CACHE_DIR)) {
            @mkdir(self::CACHE_DIR, 0755, true);
        }
    }

    /**
     * Obtém um valor do cache.
     */
    public function get(string $key): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if (!$content) {
            return null;
        }

        $data = json_decode($content, true);

        // Verifica expiração
        if (isset($data['expires_at']) && time() > $data['expires_at']) {
            @unlink($file);
            return null;
        }

        return $data['value'] ?? null;
    }

    /**
     * Salva um valor no cache.
     * @param int $ttl Tempo de vida em segundos (padrão 24h)
     */
    public function set(string $key, mixed $value, int $ttl = 86400): void
    {
        $file = $this->getFilePath($key);
        $data = [
            'expires_at' => time() + $ttl,
            'value' => $value
        ];

        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    private function getFilePath(string $key): string
    {
        return self::CACHE_DIR . '/' . md5($key) . '.cache';
    }
}
