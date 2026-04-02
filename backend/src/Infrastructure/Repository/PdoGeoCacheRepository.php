<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\GeoCacheRepositoryInterface;
use PDO;

class PdoGeoCacheRepository implements GeoCacheRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function findCoords(string $addressQuery): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT lat, lon FROM geo_cache 
            WHERE address_query = :addr 
            AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
        ');
        $stmt->execute(['addr' => mb_strtolower(trim($addressQuery))]);
        $row = $stmt->fetch();

        if ($row) {
            return [(float) $row['lat'], (float) $row['lon']];
        }

        return null;
    }

    public function saveCoords(string $addressQuery, float $lat, float $lon, int $ttlDays = 30): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO geo_cache (address_query, lat, lon, expires_at) 
            VALUES (:addr, :lat, :lon, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL :ttl DAY))
            ON DUPLICATE KEY UPDATE 
                lat = VALUES(lat), 
                lon = VALUES(lon), 
                expires_at = VALUES(expires_at)
        ');
        $stmt->execute([
            'addr' => mb_strtolower(trim($addressQuery)),
            'lat' => $lat,
            'lon' => $lon,
            'ttl' => $ttlDays
        ]);
    }

    public function findRoute(string $origin, string $destination): ?float
    {
        $stmt = $this->pdo->prepare('
            SELECT distance_km FROM route_cache 
            WHERE origin_query = :o AND destination_query = :d 
            AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
        ');
        $stmt->execute([
            'o' => mb_strtolower(trim($origin)),
            'd' => mb_strtolower(trim($destination))
        ]);
        $val = $stmt->fetchColumn();

        return $val !== false ? (float) $val : null;
    }

    public function saveRoute(string $origin, string $destination, float $distanceKm, int $ttlDays = 30): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO route_cache (origin_query, destination_query, distance_km, expires_at) 
            VALUES (:o, :d, :km, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL :ttl DAY))
            ON DUPLICATE KEY UPDATE 
                distance_km = VALUES(distance_km), 
                expires_at = VALUES(expires_at)
        ');
        $stmt->execute([
            'o' => mb_strtolower(trim($origin)),
            'd' => mb_strtolower(trim($destination)),
            'km' => $distanceKm,
            'ttl' => $ttlDays
        ]);
    }

    public function cleanExpired(): void
    {
        $this->pdo->exec('DELETE FROM geo_cache WHERE expires_at < CURRENT_TIMESTAMP');
        $this->pdo->exec('DELETE FROM route_cache WHERE expires_at < CURRENT_TIMESTAMP');
    }
}
