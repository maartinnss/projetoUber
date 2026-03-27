<?php

declare(strict_types=1);

namespace App\Domain\Entity;

class ConfiguracaoVeiculo
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $modelo,
        public readonly float $precoPorKm,
        public readonly ?string $descricao = null,
        public readonly bool $ativo = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? null),
            modelo: $data['modelo'],
            precoPorKm: (float) $data['preco_por_km'],
            descricao: $data['descricao'] ?? null,
            ativo: (bool) ($data['ativo'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'modelo' => $this->modelo,
            'preco_por_km' => $this->precoPorKm,
            'descricao' => $this->descricao,
            'ativo' => $this->ativo,
        ];
    }
}
