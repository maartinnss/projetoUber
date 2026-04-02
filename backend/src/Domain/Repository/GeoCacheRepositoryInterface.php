<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface GeoCacheRepositoryInterface
{
    /**
     * Busca coordenadas cacheadas por uma query de endereço.
     */
    public function findCoords(string $addressQuery): ?array;

    /**
     * Salva coordenadas de um endereço no cache com TTL.
     */
    public function saveCoords(string $addressQuery, float $lat, float $lon, int $ttlDays = 30): void;

    /**
     * Busca distância cacheada entre duas queries.
     */
    public function findRoute(string $origin, string $destination): ?float;

    /**
     * Salva distância de uma rota no cache com TTL.
     */
    public function saveRoute(string $origin, string $destination, float $distanceKm, int $ttlDays = 30): void;

    /**
     * Remove registros expirados do cache.
     */
    public function cleanExpired(): void;
}
