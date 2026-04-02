<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Service\EstimateService;
use App\Application\Service\WhatsAppService;
use App\Domain\ValueObject\WhatsApp;
use App\Domain\Exception\DomainException;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Http\Request;
use Exception;

class EstimateController
{
    public function __construct(
        private readonly EstimateService $estimateService,
        private readonly WhatsAppService $whatsAppService,
    ) {}

    public function estimate(Request $request, array $params = []): void
    {
        $body = $request->all();

        if (empty($body['origem']) || empty($body['destino']) || empty($body['veiculo_id'])) {
            JsonResponse::error('Campos obrigatórios: origem, destino, veiculo_id', 422);
            return;
        }

        try {
            // Valida o WhatsApp se fornecido (opcional para estimativa, mas bom validar)
            $whatsappStr = $body['whatsapp'] ?? '00000000000';
            $whatsapp = new WhatsApp($whatsappStr);

            $result = $this->estimateService->calculate(
                $body['origem'],
                $body['destino'],
                (int) $body['veiculo_id'],
            );

            // Gera link WhatsApp de pré-visualização
            $whatsappLink = $this->whatsAppService->generateLink(
                $whatsapp->getValue(),
                [
                    'origem' => $body['origem'],
                    'destino' => $body['destino'],
                    'veiculo' => $result['veiculo']['modelo'],
                    'distancia_km' => $result['distancia_km'],
                    'valor_estimado' => $result['valor_estimado'],
                ]
            );

            $result['whatsapp_link'] = $whatsappLink;

            JsonResponse::success($result);
        } catch (DomainException $e) {
            JsonResponse::error($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            JsonResponse::error('Ocorreu um erro inesperado no processamento.', 500);
        }
    }
}
