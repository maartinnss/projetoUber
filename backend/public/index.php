<?php

declare(strict_types=1);

// ─── Healthcheck Precoce (Bypass para Deploy) ─────────────────
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($uri === '/api/health' || $uri === '/') {
    http_response_code(200);
    echo "OK";
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoVeiculoRepository;
use App\Infrastructure\Repository\PdoAgendamentoRepository;
use App\Application\Service\EstimateService;
use App\Application\Service\BookingService;
use App\Application\Service\WhatsAppService;
use App\Application\Service\PlacesService;
use App\Controller\VehicleController;
use App\Controller\EstimateController;
use App\Controller\BookingController;
use App\Controller\PlacesController;
use App\Controller\ConfigController;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Router;
use App\Infrastructure\Http\RateLimiter;

// ─── Global Error Handler ───────────────────────────────────
set_exception_handler(function (\Throwable $e) {
    // Aqui um Monolog poderia ser usado num projeto em prod
    error_log((string)$e);
    JsonResponse::error('Ocorreu um erro interno no servidor (Verifique os logs).', 500);
});

// Nota: Headers CORS foram removidos do index.php
// Eles já estão sendo geridos na camada de proxy do Nginx (default.conf)
// Evitando assim o erro bloqueante do navegador "Multiple CORS header".

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Rate Limiter Middleware ───────────────────────────────────
$rateLimiter = new RateLimiter();
if (!$rateLimiter->allow($uri)) {
    JsonResponse::error('Muitas requisições. Tente novamente em alguns segundos.', 429);
    exit;
}

// ─── DI & Routing (Lazy Loading pattern) ──────────────────────
$router = new Router();
$request = new Request();

// Rota de configurações públicas
$router->get('/api/config', [new ConfigController(), 'index']);

// Geocodificação (Search)
$router->get('/api/places', [new PlacesController(new PlacesService()), 'search']);

// Veículos
$pdo = Connection::getInstance();
$veiculoRepo = new PdoVeiculoRepository($pdo);
$vehicleController = new VehicleController($veiculoRepo);

$router->get('/api/vehicles', [$vehicleController, 'index']);
$router->get('/api/vehicles/{id}', [$vehicleController, 'show']); // Nova rota dinâmica!

// Estimativa
$placesService = new PlacesService();
$estimateService = new EstimateService($veiculoRepo, $placesService);
$whatsAppService = new WhatsAppService();
$estimateController = new EstimateController($estimateService, $whatsAppService);
$router->post('/api/estimate', [$estimateController, 'estimate']);

// Agendamento
$agendamentoRepo = new PdoAgendamentoRepository($pdo);
$bookingService = new BookingService($agendamentoRepo, $veiculoRepo, $estimateService, $whatsAppService);
$bookingController = new BookingController($bookingService);
$router->post('/api/booking', [$bookingController, 'store']);

$router->dispatch($request);
