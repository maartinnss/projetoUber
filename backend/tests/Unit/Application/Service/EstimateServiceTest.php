<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\EstimateService;
use App\Application\Service\PlacesService;
use App\Domain\Entity\ConfiguracaoVeiculo;
use App\Domain\Exception\VehicleNotFoundException;
use App\Domain\Repository\GeoCacheRepositoryInterface;
use App\Domain\Repository\VeiculoRepositoryInterface;
use App\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class EstimateServiceTest extends TestCase
{
    private VeiculoRepositoryInterface|MockObject $veiculoRepo;
    private PlacesService|MockObject $placesService;
    private GeoCacheRepositoryInterface|MockObject $cacheRepo;
    private LoggerInterface|MockObject $logger;
    private EstimateService $estimateService;

    protected function setUp(): void
    {
        $this->veiculoRepo = $this->createMock(VeiculoRepositoryInterface::class);
        $this->placesService = $this->createMock(PlacesService::class);
        $this->cacheRepo = $this->createMock(GeoCacheRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->estimateService = new EstimateService(
            $this->veiculoRepo,
            $this->placesService,
            $this->cacheRepo,
            $this->logger
        );
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

        // Mock cache miss
        $this->cacheRepo->expects($this->once())
            ->method('findRoute')
            ->willReturn(null);

        // Mock geocoding para evitar chamadas externas no teste unitário
        $this->cacheRepo->expects($this->exactly(2))
            ->method('findCoords')
            ->willReturn(null);

        $this->placesService->expects($this->exactly(2))
            ->method('search')
            ->willReturnMap([
                [$origem, [['lat' => -27.5, 'lon' => -48.5]]],
                [$destino, [['lat' => -27.6, 'lon' => -48.6]]],
            ]);

        $this->cacheRepo->expects($this->exactly(2))
            ->method('saveCoords');

        $this->cacheRepo->expects($this->once())
            ->method('saveRoute');
        
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

        $this->expectException(VehicleNotFoundException::class);

        $this->estimateService->calculate('A', 'B', 999);
    }
}
