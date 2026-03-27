<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Service\BookingService;
use App\Infrastructure\Http\JsonResponse;

class BookingController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function store(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body) {
            JsonResponse::error('JSON inválido no corpo da requisição.', 400);
            return;
        }

        try {
            $result = $this->bookingService->create($body);
            JsonResponse::success($result, 201);
        } catch (\InvalidArgumentException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            JsonResponse::error('Erro interno do servidor.', 500);
        }
    }
}
