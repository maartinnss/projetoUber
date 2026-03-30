<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Repository\VeiculoRepositoryInterface;

class EstimateService
{
    public function __construct(
        private readonly VeiculoRepositoryInterface $veiculoRepo,
        private readonly PlacesService $placesService,
    ) {}

    /**
     * Calcula estimativa de valor da corrida.
     */
    public function calculate(string $origem, string $destino, int $veiculoId): array
    {
        $veiculo = $this->veiculoRepo->findById($veiculoId);

        if (!$veiculo) {
            throw new \InvalidArgumentException('Veículo não encontrado.');
        }

        // Cálculo de distância com fallback
        $distanciaKm = $this->estimateDistance($origem, $destino);
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
            return $this->fallbackEstimateDistance($origem, $destino);
        }

        // OSRM aceita coordenadas no formato lon,lat
        $coordsString = "{$origemCoords[1]},{$origemCoords[0]};{$destinoCoords[1]},{$destinoCoords[0]}";
        
        $url = "http://router.project-osrm.org/route/v1/driving/{$coordsString}?overview=false";

        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: DriverEliteApp/1.0\r\n",
                'timeout' => 4.0 // Limita a espera em 4 segundos para evitar travamento infinito no frontend
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['routes'][0]['distance'])) {
                // Distância é dada em metros
                $km = round($data['routes'][0]['distance'] / 1000, 1);
                
                // Previne rotas irreais (ex: OSRM retornar 0 ou quilometros interplanetários)
                $haversine = $this->haversineGreatCircleDistance($origemCoords[0], $origemCoords[1], $destinoCoords[0], $destinoCoords[1]);
                
                // Se a distância de rota for maior que 4 vezes a linha reta ou zero, é falsa.
                if ($km < 0.1 || $km > ($haversine * 4)) {
                    return round($haversine * 1.35, 1); // Multiplicador de sinuosidade
                }
                
                return $km;
            }
        }

        return $this->fallbackEstimateDistance($origem, $destino);
    }

    /**
     * Obtém as coordenadas usando o Photon (komoot).
     * Reutilizamos o PlacesService para garantia de busca.
     */
    private function geocode(string $address): ?array
    {
        $results = $this->placesService->search($address);
        if (!empty($results)) {
            // Pega o Top 1
            return [$results[0]['lat'], $results[0]['lon']];
        }
        return null;
    }

    /**
     * Gera distância determinística caso a API falhe totalmente.
     */
    private function fallbackEstimateDistance(string $origem, string $destino): float
    {
        // Se as APIs locais caírem, tenta usar o haversine antes de desistir
        $origemCoords = $this->geocode($origem);
        $destinoCoords = $this->geocode($destino);
        
        if ($origemCoords && $destinoCoords) {
            $linhaReta = $this->haversineGreatCircleDistance($origemCoords[0], $origemCoords[1], $destinoCoords[0], $destinoCoords[1]);
            return round($linhaReta * 1.35, 1); // multiplicador médio de ruído urbano
        }

        // Caso a Geocodificação (Photon) também tenha caído
        $seed = crc32(mb_strtolower(trim($origem) . '|' . trim($destino)));
        $normalized = abs($seed) / 4294967295; // Normaliza para 0-1
        $distancia = 5 + ($normalized * 40); // Entre 5 e 45 km

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
