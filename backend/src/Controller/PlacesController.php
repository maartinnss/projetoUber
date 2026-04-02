<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Service\PlacesService;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Http\Request;

class PlacesController
{
    public function __construct(
        private readonly PlacesService $placesService
    ) {}

    public function search(Request $request, array $params = []): void
    {
        $query = $request->get('q', '');

        if (strlen($query) < 3) {
            JsonResponse::success([]);
            return;
        }

        try {
            $suggestions = $this->placesService->search($query);
            JsonResponse::success($suggestions);
        } catch (\Throwable $e) {
            JsonResponse::error('Falha ao buscar endereços.', 500);
        }
    }
}
