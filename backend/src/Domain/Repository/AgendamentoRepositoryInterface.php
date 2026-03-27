<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Agendamento;

interface AgendamentoRepositoryInterface
{
    public function save(Agendamento $agendamento): int;

    public function findById(int $id): ?Agendamento;
}
