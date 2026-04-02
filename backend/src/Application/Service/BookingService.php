<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Agendamento;
use App\Domain\Repository\AgendamentoRepositoryInterface;
use App\Domain\Repository\VeiculoRepositoryInterface;
use DateTimeImmutable;

class BookingService
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $agendamentoRepo,
        private readonly VeiculoRepositoryInterface $veiculoRepo,
        private readonly EstimateService $estimateService,
        private readonly WhatsAppService $whatsAppService,
    ) {}

    /**
     * Cria um novo agendamento e retorna os dados com link WhatsApp.
     */
    public function create(
        string $nome,
        string $whatsapp,
        string $origem,
        string $destino,
        DateTimeImmutable $dataHora,
        int $veiculoId
    ): array {
        // Calcula estimativa
        $estimate = $this->estimateService->calculate(
            $origem,
            $destino,
            $veiculoId
        );

        $agendamento = new Agendamento(
            id: null,
            nome: $nome,
            whatsapp: $whatsapp,
            origem: $origem,
            destino: $destino,
            dataHora: $dataHora,
            veiculoId: $veiculoId,
            distanciaKm: $estimate['distancia_km'],
            valorEstimado: $estimate['valor_estimado'],
            status: 'pendente',
        );

        $id = $this->agendamentoRepo->save($agendamento);

        // Gera link WhatsApp
        $whatsappLink = $this->whatsAppService->generateLink($whatsapp, [
            'nome' => $nome,
            'origem' => $origem,
            'destino' => $destino,
            'data_hora' => $dataHora->format('Y-m-d H:i:s'),
            'veiculo' => $estimate['veiculo']['modelo'],
            'distancia_km' => $estimate['distancia_km'],
            'valor_estimado' => $estimate['valor_estimado'],
        ]);

        return [
            'id' => $id,
            'agendamento' => $agendamento->toArray(),
            'whatsapp_link' => $whatsappLink,
        ];
    }
}
