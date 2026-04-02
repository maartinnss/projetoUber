<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Exception\VehicleNotFoundException;
use App\Domain\Repository\GeoCacheRepositoryInterface;
use App\Domain\Repository\VeiculoRepositoryInterface;
use Psr\Log\LoggerInterface;

class EstimateService
{
    public function __construct(
        private readonly VeiculoRepositoryInterface $veiculoRepo,
        private readonly PlacesService $placesService,
        private readonly GeoCacheRepositoryInterface $cacheRepo,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Calcula estimativa de valor da corrida.
     */
    public function calculate(string $origem, string $destino, int $veiculoId): array
    {
        $veiculo = $this->veiculoRepo->findById($veiculoId);

        if (!$veiculo) {
            throw new VehicleNotFoundException();
        }

        // Tenta pegar do cache de rotas primeiro
        $distanciaKm = $this->cacheRepo->findRoute($origem, $destino);
        
        if ($distanciaKm === null) {
            // Cálculo de distância com fallback
            $distanciaKm = $this->estimateDistance($origem, $destino);
            $this->cacheRepo->saveRoute($origem, $destino, $distanciaKm);
        }

        $valorEstimado = round($distanciaKm * $veiculo->precoPorKm, 2);

        return [
            'origem' => $origem,
            'destino' => $destino,
            'distancia_km' => $distanciaKm,
            'valor_estimado' => $valorEstimado,
            'veiculo' => $veiculo->toArray(),
        ];
    }

    /**
     * Calcula distância real usando OSRM e nova base do Photon.
     */
    private function estimateDistance(string $origem, string $destino): float
    {
        $origemCoords = $this->geocode($origem);
        $destinoCoords = $this->geocode($destino);

        if (!$origemCoords || !$destinoCoords) {
            $this->logger->warning("Falha ao geocodificar origem ou destino para fallback.", ['origem' => $origem, 'destino' => $destino]);
            return $this->fallbackEstimateDistance($origem, $destino);
        }

        // OSRM aceita coordenadas no formato lon,lat
        $coordsString = "{$origemCoords[1]},{$origemCoords[0]};{$destinoCoords[1]},{$destinoCoords[0]}";
        
        $params = ['overview' => 'false'];
        $url = "http://router.project-osrm.org/route/v1/driving/" . rawurlencode($coordsString) . "?" . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'DriverEliteApp/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['routes'][0]['distance'])) {
                $km = round($data['routes'][0]['distance'] / 1000, 1);
                
                $haversine = $this->haversineGreatCircleDistance($origemCoords[0], $origemCoords[1], $destinoCoords[0], $destinoCoords[1]);
                
                if ($km < 0.1 || $km > ($haversine * 4)) {
                    $this->logger->notice("Distância OSRM irreal detected. Usando Haversine corrigido.", ['osrm_km' => $km, 'haversine' => $haversine]);
                    return round($haversine * 1.35, 1);
                }
                
                return $km;
            }
        }

        $this->logger->error("OSRM falhou ou retornou erro.", ['http_code' => $httpCode, 'error' => $error]);

        $haversine = $this->haversineGreatCircleDistance($origemCoords[0], $origemCoords[1], $destinoCoords[0], $destinoCoords[1]);
        return round($haversine * 1.35, 1);
    }

    private function geocode(string $address): ?array
    {
        // Tenta cache primeiro
        $coords = $this->cacheRepo->findCoords($address);
        if ($coords) {
            return $coords;
        }

        // API Externa via PlacesService
        try {
            $results = $this->placesService->search($address);
            if (!empty($results)) {
                $lat = (float) $results[0]['lat'];
                $lon = (float) $results[0]['lon'];
                
                $this->cacheRepo->saveCoords($address, $lat, $lon);
                
                return [$lat, $lon];
            }
        } catch (\Exception $e) {
            $this->logger->error("Erro ao consumir PlacesService.", ['address' => $address, 'exception' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Gera distância determinística caso todas as APIs falhem.
     */
    private function fallbackEstimateDistance(string $origem, string $destino): float
    {
        $seed = crc32(mb_strtolower(trim($origem) . '|' . trim($destino)));
        $normalized = abs($seed) / 4294967295;
        $distancia = 5 + ($normalized * 40); 

        return round($distancia, 1);
    }
    
    /**
     * Calcula distância em linha reta (Haversine Formula) em KM
     */
    private function haversineGreatCircleDistance(float $latFrom, float $lonFrom, float $latTo, float $lonTo, int $earthRadius = 6371): float
    {
        $latDelta = deg2rad($latTo - $latFrom);
        $lonDelta = deg2rad($lonTo - $lonFrom);

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
          cos(deg2rad($latFrom)) * cos(deg2rad($latTo)) * pow(sin($lonDelta / 2), 2)));
          
        return $angle * $earthRadius;
    }
}
