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
    public function create(array $data): array
    {
        $this->validate($data);

        // Calcula estimativa
        $estimate = $this->estimateService->calculate(
            $data['origem'],
            $data['destino'],
            (int) $data['veiculo_id']
        );

        $agendamento = new Agendamento(
            id: null,
            nome: $data['nome'],
            whatsapp: $data['whatsapp'],
            origem: $data['origem'],
            destino: $data['destino'],
            dataHora: new DateTimeImmutable($data['data_hora']),
            veiculoId: (int) $data['veiculo_id'],
            distanciaKm: $estimate['distancia_km'],
            valorEstimado: $estimate['valor_estimado'],
            status: 'pendente',
        );

        $id = $this->agendamentoRepo->save($agendamento);

        // Gera link WhatsApp
        $whatsappLink = $this->whatsAppService->generateLink($data['whatsapp'], [
            'nome' => $data['nome'],
            'origem' => $data['origem'],
            'destino' => $data['destino'],
            'data_hora' => $data['data_hora'],
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

    private function validate(array $data): void
    {
        $required = ['nome', 'whatsapp', 'origem', 'destino', 'data_hora', 'veiculo_id'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Campo obrigatório ausente: {$field}");
            }
        }
    }
}
