<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ConfiguracaoVeiculo;

interface VeiculoRepositoryInterface
{
    /** @return ConfiguracaoVeiculo[] */
    public function findAllActive(): array;

    public function findById(int $id): ?ConfiguracaoVeiculo;
}
