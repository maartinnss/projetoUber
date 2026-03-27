<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

class Agendamento
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nome,
        public readonly string $whatsapp,
        public readonly string $origem,
        public readonly string $destino,
        public readonly DateTimeImmutable $dataHora,
        public readonly ?int $veiculoId = null,
        public readonly ?float $distanciaKm = null,
        public readonly ?float $valorEstimado = null,
        public readonly string $status = 'pendente',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            nome: $data['nome'],
            whatsapp: $data['whatsapp'],
            origem: $data['origem'],
            destino: $data['destino'],
            dataHora: new DateTimeImmutable($data['data_hora']),
            veiculoId: isset($data['veiculo_id']) ? (int) $data['veiculo_id'] : null,
            distanciaKm: isset($data['distancia_km']) ? (float) $data['distancia_km'] : null,
            valorEstimado: isset($data['valor_estimado']) ? (float) $data['valor_estimado'] : null,
            status: $data['status'] ?? 'pendente',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'whatsapp' => $this->whatsapp,
            'origem' => $this->origem,
            'destino' => $this->destino,
            'data_hora' => $this->dataHora->format('Y-m-d H:i:s'),
            'veiculo_id' => $this->veiculoId,
            'distancia_km' => $this->distanciaKm,
            'valor_estimado' => $this->valorEstimado,
            'status' => $this->status,
        ];
    }
}
