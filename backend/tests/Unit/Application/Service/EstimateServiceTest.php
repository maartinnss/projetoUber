<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\EstimateService;
use App\Application\Service\PlacesService;
use App\Domain\Entity\ConfiguracaoVeiculo;
use App\Domain\Repository\VeiculoRepositoryInterface;
use App\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EstimateServiceTest extends TestCase
{
    private VeiculoRepositoryInterface|MockObject $veiculoRepo;
    private PlacesService|MockObject $placesService;
    private EstimateService $estimateService;

    protected function setUp(): void
    {
        $this->veiculoRepo = $this->createMock(VeiculoRepositoryInterface::class);
        $this->placesService = $this->createMock(PlacesService::class);
        $this->estimateService = new EstimateService($this->veiculoRepo, $this->placesService);
    }

    public function testCalculateSuccess(): void
    {
        $origem = 'Rua A, Florianópolis';
        $destino = 'Rua B, Florianópolis';
        $veiculoId = 1;

        $veiculo = new ConfiguracaoVeiculo(
            id: 1,
            modelo: 'Sedan Teste',
            tipo: 'sedan',
            capacidadePassageiros: 4,
            precoPorKm: 3.50
        );

        $this->veiculoRepo->expects($this->once())
            ->method('findById')
            ->with($veiculoId)
            ->willReturn($veiculo);

        // Mock geocoding para evitar chamadas externas no teste unitário
        $this->placesService->expects($this->exactly(2))
            ->method('search')
            ->willReturnMap([
                [$origem, [['lat' => -27.5, 'lon' => -48.5]]],
                [$destino, [['lat' => -27.6, 'lon' => -48.6]]],
            ]);

        // Como OSRM é chamado via cURL internamente no EstimateService 
        // e não está abstraído em um Client, o teste cairá no fallback 
        // determinístico ou haversine se o cURL falhar/mocked.
        
        $result = $this->estimateService->calculate($origem, $destino, $veiculoId);

        $this->assertArrayHasKey('distancia_km', $result);
        $this->assertArrayHasKey('valor_estimado', $result);
        $this->assertEquals('Sedan Teste', $result['veiculo']['modelo']);
        $this->assertGreaterThan(0, $result['distancia_km']);
        $this->assertGreaterThan(0, $result['valor_estimado']);
    }

    public function testCalculateThrowsExceptionWhenVehicleNotFound(): void
    {
        $this->veiculoRepo->method('findById')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Veículo não encontrado.');

        $this->estimateService->calculate('A', 'B', 999);
    }
}
