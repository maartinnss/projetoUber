<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Repository\VeiculoRepositoryInterface;

class EstimateService
{
    public function __construct(
        private readonly VeiculoRepositoryInterface $veiculoRepo,
    ) {}

    /**
     * Calcula estimativa de valor da corrida.
     * Usa distância simulada baseada em string de endereço (produção usaria Geocoding API).
     */
    public function calculate(string $origem, string $destino, int $veiculoId): array
    {
        $veiculo = $this->veiculoRepo->findById($veiculoId);

        if (!$veiculo) {
            throw new \InvalidArgumentException('Veículo não encontrado.');
        }

        // Simula distância baseada no hash dos endereços (entre 5 e 45 km)
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
     * Calcula distância simulada entre dois endereços.
     * Em produção, substituir por Google Maps Distance Matrix API ou similar.
     */
    private function estimateDistance(string $origem, string $destino): float
    {
        // Gera uma distância determinística (mesmo input = mesmo output)
        $seed = crc32(mb_strtolower(trim($origem) . '|' . trim($destino)));
        $normalized = abs($seed) / 4294967295; // Normaliza para 0-1
        $distancia = 5 + ($normalized * 40); // Entre 5 e 45 km

        return round($distancia, 1);
    }
}
