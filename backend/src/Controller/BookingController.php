<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Service\BookingService;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Http\Request;

class BookingController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function store(Request $request): void
    {
        $body = $request->all();

        if (empty($body)) {
            JsonResponse::error('Requisição sem payload (JSON inválido/vazio).', 400);
            return;
        }

        try {
            $result = $this->bookingService->create($body);
            JsonResponse::success($result, 201);
        } catch (\InvalidArgumentException $e) {
            JsonResponse::error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            error_log('[BookingController] Erro não tratado: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            JsonResponse::error('Erro interno do servidor.', 500);
        }
    }
}
