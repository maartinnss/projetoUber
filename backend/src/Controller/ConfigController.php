<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Http\Request;

class ConfigController
{
    public function index(Request $request): void
    {
        JsonResponse::success([
            'whatsapp_number' => getenv('WHATSAPP_NUMBER') ?: '554896643792',
            'app_name' => 'DriverElite',
        ]);
    }
}
