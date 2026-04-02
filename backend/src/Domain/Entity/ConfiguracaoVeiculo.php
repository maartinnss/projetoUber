<?php

declare(strict_types=1);

namespace App\Domain\Entity;

class ConfiguracaoVeiculo
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $modelo,
        public readonly string $tipo = 'sedan',
        public readonly int $capacidadePassageiros = 4,
        public readonly ?string $imagemUrl = null,
        public readonly float $precoPorKm = 3.50,
        public readonly ?string $descricao = null,
        public readonly bool $ativo = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            modelo: $data['modelo'],
            tipo: $data['tipo'] ?? 'sedan',
            capacidadePassageiros: (int) ($data['capacidade_passageiros'] ?? 4),
            imagemUrl: $data['imagem_url'] ?? null,
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
            'tipo' => $this->tipo,
            'capacidade_passageiros' => $this->capacidadePassageiros,
            'imagem_url' => $this->imagemUrl,
            'preco_por_km' => $this->precoPorKm,
            'descricao' => $this->descricao,
            'ativo' => $this->ativo,
        ];
    }
}

