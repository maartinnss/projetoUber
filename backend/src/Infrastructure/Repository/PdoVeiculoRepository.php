<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\ConfiguracaoVeiculo;
use App\Domain\Repository\VeiculoRepositoryInterface;
use PDO;

class PdoVeiculoRepository implements VeiculoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function findAllActive(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM configuracoes_veiculo WHERE ativo = 1 ORDER BY preco_por_km ASC'
        );

        $veiculos = [];
        while ($row = $stmt->fetch()) {
            $veiculos[] = ConfiguracaoVeiculo::fromArray($row);
        }

        return $veiculos;
    }

    public function findById(int $id): ?ConfiguracaoVeiculo
    {
        $stmt = $this->pdo->prepare('SELECT * FROM configuracoes_veiculo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? ConfiguracaoVeiculo::fromArray($row) : null;
    }
}
