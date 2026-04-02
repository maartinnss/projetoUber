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

use App\Controller\BookingController;
use App\Controller\ConfigController;
use App\Controller\EstimateController;
use App\Controller\PlacesController;
use App\Controller\VehicleController;
use App\Infrastructure\Http\JsonResponse;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Router;
use App\Infrastructure\Http\RateLimiter;
use App\Infrastructure\Http\SecurityGuard;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;

// ─── Bootstrap DI Container ───────────────────────────────────
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/Infrastructure/Container/definitions.php');

try {
    $container = $containerBuilder->build();
} catch (\Exception $e) {
    http_response_code(500);
    echo "Erro fatal na inicialização do sistema.";
    exit;
}

$logger = $container->get(LoggerInterface::class);
$securityGuard = $container->get(SecurityGuard::class);

// ─── Security Check (Origin/CSRF) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$securityGuard->checkOrigin()) {
        $logger->warning('Tentativa de acesso de origem não autorizada.', [
            'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'N/A',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'N/A',
        ]);
        JsonResponse::error('Acesso não autorizado.', 403);
        exit;
    }
}

// ─── Global Error Handler (Monolog) ───────────────────────────
set_exception_handler(function (\Throwable $e) use ($logger) {
    // Log estruturado com Monolog
    $logger->error($e->getMessage(), [
        'exception' => $e,
        'trace' => $e->getTraceAsString(),
        'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
    ]);
    
    JsonResponse::error('Ocorreu um erro interno no servidor. Verifique os logs do sistema.', 500);
});

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

// ─── Routing ──────────────────────────────────────────────────
$router = new Router();
$request = new Request();

// Rota de configurações públicas
$router->get('/api/config', [$container->get(ConfigController::class), 'index']);

// Geocodificação (Search)
$router->get('/api/places', [$container->get(PlacesController::class), 'search']);

// Veículos
$router->get('/api/vehicles', [$container->get(VehicleController::class), 'index']);
$router->get('/api/vehicles/{id}', [$container->get(VehicleController::class), 'show']);

// Estimativa
$router->post('/api/estimate', [$container->get(EstimateController::class), 'estimate']);

// Agendamento
$router->post('/api/booking', [$container->get(BookingController::class), 'store']);

$router->dispatch($request);
