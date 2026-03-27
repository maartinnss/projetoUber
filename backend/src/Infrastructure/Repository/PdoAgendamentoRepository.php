<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Agendamento;
use App\Domain\Repository\AgendamentoRepositoryInterface;
use PDO;

class PdoAgendamentoRepository implements AgendamentoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    public function save(Agendamento $agendamento): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO agendamentos (nome, whatsapp, origem, destino, data_hora, veiculo_id, distancia_km, valor_estimado, status)
             VALUES (:nome, :whatsapp, :origem, :destino, :data_hora, :veiculo_id, :distancia_km, :valor_estimado, :status)'
        );

        $stmt->execute([
            'nome' => $agendamento->nome,
            'whatsapp' => $agendamento->whatsapp,
            'origem' => $agendamento->origem,
            'destino' => $agendamento->destino,
            'data_hora' => $agendamento->dataHora->format('Y-m-d H:i:s'),
            'veiculo_id' => $agendamento->veiculoId,
            'distancia_km' => $agendamento->distanciaKm,
            'valor_estimado' => $agendamento->valorEstimado,
            'status' => $agendamento->status,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?Agendamento
    {
        $stmt = $this->pdo->prepare('SELECT * FROM agendamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Agendamento::fromArray($row) : null;
    }
}
