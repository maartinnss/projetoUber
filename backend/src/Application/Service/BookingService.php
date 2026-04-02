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
        $data = $this->normalizeFields($data);
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

    /**
     * Normaliza campos de entrada (compatibilidade frontend/backend).
     */
    private function normalizeFields(array $data): array
    {
        // Frontend envia 'nome_cliente', backend espera 'nome'
        if (isset($data['nome_cliente']) && !isset($data['nome'])) {
            $data['nome'] = $data['nome_cliente'];
        }

        // Sanitiza strings contra XSS
        $textFields = ['nome', 'whatsapp', 'origem', 'destino'];
        foreach ($textFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = htmlspecialchars(strip_tags(trim($data[$field])), ENT_QUOTES, 'UTF-8');
            }
        }

        return $data;
    }

    private function validate(array $data): void
    {
        $required = ['nome', 'whatsapp', 'origem', 'destino', 'data_hora', 'veiculo_id'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Campo obrigatório ausente: {$field}");
            }
        }

        // Validação de tamanho máximo
        $maxLengths = ['nome' => 100, 'whatsapp' => 20, 'origem' => 255, 'destino' => 255];
        foreach ($maxLengths as $field => $max) {
            if (isset($data[$field]) && mb_strlen($data[$field]) > $max) {
                throw new \InvalidArgumentException("Campo '{$field}' excede o tamanho máximo de {$max} caracteres.");
            }
        }

        // Validação de formato de telefone (apenas dígitos, parênteses, espaços e hífens)
        $phoneClean = preg_replace('/[^0-9]/', '', $data['whatsapp']);
        if (strlen($phoneClean) < 10 || strlen($phoneClean) > 15) {
            throw new \InvalidArgumentException('Número de WhatsApp inválido. Use formato: (XX) XXXXX-XXXX');
        }

        // Validação de data futura
        try {
            $dataHora = new DateTimeImmutable($data['data_hora']);
            $agora = new DateTimeImmutable();
            if ($dataHora < $agora) {
                throw new \InvalidArgumentException('A data/hora deve ser no futuro.');
            }
        } catch (\Exception $e) {
            if ($e instanceof \InvalidArgumentException) {
                throw $e;
            }
            throw new \InvalidArgumentException('Data/hora inválida.');
        }

        // Validação de veículo ID (inteiro positivo)
        $veiculoId = filter_var($data['veiculo_id'], FILTER_VALIDATE_INT);
        if ($veiculoId === false || $veiculoId <= 0) {
            throw new \InvalidArgumentException('ID de veículo inválido.');
        }
    }
}
