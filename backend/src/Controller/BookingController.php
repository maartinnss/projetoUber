<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Service\BookingService;
use App\Domain\ValueObject\WhatsApp;
use App\Domain\ValueObject\ScheduleDateTime;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Http\Request;
use InvalidArgumentException;

class BookingController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function store(Request $request, array $params = []): void
    {
        $body = $request->all();

        // Validação inicial de campos presentes
        $required = ['nome_cliente', 'whatsapp', 'origem', 'destino', 'data_hora', 'veiculo_id'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                JsonResponse::error("O campo {$field} é obrigatório.", 422);
                return;
            }
        }

        try {
            // Conversão para Value Objects (Lógica de Domínio)
            $whatsapp = new WhatsApp($body['whatsapp']);
            $dataHora = new ScheduleDateTime($body['data_hora']);

            $this->bookingService->create(
                $body['nome_cliente'],
                $whatsapp->getValue(),
                $body['origem'],
                $body['destino'],
                $dataHora->getValue(),
                (int) $body['veiculo_id'],
            );

            JsonResponse::success(['message' => 'Agendamento realizado com sucesso!']);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            // Este log será capturado pelo Global Error Handler
            throw $e;
        }
    }
}
