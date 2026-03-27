<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Repository\VeiculoRepositoryInterface;
use App\Infrastructure\Http\JsonResponse;

class VehicleController
{
    public function __construct(
        private readonly VeiculoRepositoryInterface $veiculoRepo,
    ) {}

    public function index(): void
    {
        $veiculos = $this->veiculoRepo->findAllActive();

        $result = array_map(fn($v) => $v->toArray(), $veiculos);

        JsonResponse::success($result);
    }
}
