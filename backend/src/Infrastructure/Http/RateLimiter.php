<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

/**
 * Rate Limiter simples baseado em filesystem.
 * Armazena contagem de requests por IP em /tmp.
 * Para produção com múltiplas instâncias, migrar para Redis.
 */
class RateLimiter
{
    private const STORAGE_DIR = '/tmp/rate_limits';

    /** Limites por rota (requests por janela de tempo) */
    private const ROUTE_LIMITS = [
        '/api/places'   => ['max' => 30, 'window' => 60],   // 30 req/min (autocomplete)
        '/api/estimate' => ['max' => 10, 'window' => 60],   // 10 req/min
        '/api/booking'  => ['max' => 5,  'window' => 300],  // 5 req/5min
        'default'       => ['max' => 60, 'window' => 60],   // 60 req/min global
    ];

    public function __construct()
    {
        if (!is_dir(self::STORAGE_DIR)) {
            @mkdir(self::STORAGE_DIR, 0755, true);
        }
    }

    public function allow(string $uri): bool
    {
        $ip = $this->getClientIp();
        $routeKey = $this->resolveRouteKey($uri);
        $limits = self::ROUTE_LIMITS[$routeKey] ?? self::ROUTE_LIMITS['default'];

        $file = self::STORAGE_DIR . '/' . md5($ip . $routeKey) . '.json';

        $data = $this->readFile($file);
        $now = time();

        // Limpa entradas expiradas
        if ($now - ($data['window_start'] ?? 0) >= $limits['window']) {
            $data = ['window_start' => $now, 'count' => 0];
        }

        $data['count']++;

        $this->writeFile($file, $data);

        return $data['count'] <= $limits['max'];
    }

    private function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    private function resolveRouteKey(string $uri): string
    {
        foreach (self::ROUTE_LIMITS as $prefix => $limits) {
            if ($prefix !== 'default' && str_starts_with($uri, $prefix)) {
                return $prefix;
            }
        }
        return 'default';
    }

    private function readFile(string $file): array
    {
        if (!file_exists($file)) {
            return ['window_start' => time(), 'count' => 0];
        }

        $content = @file_get_contents($file);
        $data = json_decode($content ?: '', true);

        return is_array($data) ? $data : ['window_start' => time(), 'count' => 0];
    }

    private function writeFile(string $file, array $data): void
    {
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }
}
