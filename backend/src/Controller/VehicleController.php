<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Repository\VeiculoRepositoryInterface;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Http\Request;

class VehicleController
{
    public function __construct(
        private readonly VeiculoRepositoryInterface $veiculoRepo,
    ) {}

    public function index(Request $request, array $params = []): void
    {
        $veiculos = $this->veiculoRepo->findAllActive();

        $result = array_map(fn($v) => $v->toArray(), $veiculos);

        JsonResponse::success($result);
    }

    public function show(Request $request, array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $veiculo = $this->veiculoRepo->findById($id);

        if (!$veiculo) {
            JsonResponse::error('Veículo não encontrado.', 404);
            return;
        }

        JsonResponse::success($veiculo->toArray());
    }
}
