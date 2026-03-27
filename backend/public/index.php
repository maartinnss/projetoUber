<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoVeiculoRepository;
use App\Infrastructure\Repository\PdoAgendamentoRepository;
use App\Application\Service\EstimateService;
use App\Application\Service\BookingService;
use App\Application\Service\WhatsAppService;
use App\Controller\VehicleController;
use App\Controller\EstimateController;
use App\Controller\BookingController;
use App\Infrastructure\Http\JsonResponse;

// ─── CORS ───────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Router & Healthcheck ─────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'GET' && $uri === '/api/health') {
    JsonResponse::success(['status' => 'ok', 'timestamp' => date('c')]);
    exit;
}
if ($method === 'GET' && $uri === '/') {
    // Caso de requisição cair aqui erradamente, serve 200 para saúde
    echo "OK"; 
    exit;
}

// ─── DI (Poor Man's Container) ──────────────────────────────
try {
    $pdo = Connection::getInstance();
} catch (\Throwable $e) {
    JsonResponse::error('Database unavailable: ' . $e->getMessage(), 503);
    exit;
}

$veiculoRepo  = new PdoVeiculoRepository($pdo);
$agendamentoRepo = new PdoAgendamentoRepository($pdo);
$whatsAppService = new WhatsAppService();
$estimateService = new EstimateService($veiculoRepo);
$bookingService  = new BookingService($agendamentoRepo, $veiculoRepo, $estimateService, $whatsAppService);

$vehicleController  = new VehicleController($veiculoRepo);
$estimateController = new EstimateController($estimateService, $whatsAppService);
$bookingController  = new BookingController($bookingService);

match (true) {
    $method === 'GET'  && $uri === '/api/vehicles'  => $vehicleController->index(),
    $method === 'POST' && $uri === '/api/estimate'   => $estimateController->estimate(),
    $method === 'POST' && $uri === '/api/booking'    => $bookingController->store(),

    // 404
    default => JsonResponse::error('Rota não encontrada: ' . $method . ' ' . $uri, 404),
};
