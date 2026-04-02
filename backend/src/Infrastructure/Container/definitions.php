<?php

declare(strict_types=1);

use App\Application\Service\BookingService;
use App\Application\Service\EstimateService;
use App\Application\Service\PlacesService;
use App\Application\Service\WhatsAppService;
use App\Controller\BookingController;
use App\Controller\ConfigController;
use App\Controller\EstimateController;
use App\Controller\PlacesController;
use App\Controller\VehicleController;
use App\Domain\Repository\AgendamentoRepositoryInterface;
use App\Domain\Repository\GeoCacheRepositoryInterface;
use App\Domain\Repository\VeiculoRepositoryInterface;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoAgendamentoRepository;
use App\Infrastructure\Repository\PdoGeoCacheRepository;
use App\Infrastructure\Repository\PdoVeiculoRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use function DI\create;
use function DI\get;

use App\Infrastructure\Http\SecurityGuard;

return [
    // ─── Infrastructure ───
    PDO::class => function () {
        return Connection::getInstance();
    },

    SecurityGuard::class => create(SecurityGuard::class),

    LoggerInterface::class => function () {
        $logger = new Logger('driver-elite');
        // Log para console/error_log (docker logs)
        $logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));
        // Log para arquivo se necessário (opcional em Docker)
        // $logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/app.log', Logger::INFO));
        return $logger;
    },

    // ─── Repositories ───
    VeiculoRepositoryInterface::class => create(PdoVeiculoRepository::class)
        ->constructor(get(PDO::class)),

    AgendamentoRepositoryInterface::class => create(PdoAgendamentoRepository::class)
        ->constructor(get(PDO::class)),

    GeoCacheRepositoryInterface::class => create(PdoGeoCacheRepository::class)
        ->constructor(get(PDO::class)),

    // ─── Services ───
    PlacesService::class => create(PlacesService::class),
    
    WhatsAppService::class => create(WhatsAppService::class),

    EstimateService::class => create(EstimateService::class)
        ->constructor(
            get(VeiculoRepositoryInterface::class),
            get(PlacesService::class),
            get(GeoCacheRepositoryInterface::class),
            get(Psr\Log\LoggerInterface::class)
        ),

    BookingService::class => create(BookingService::class)
        ->constructor(
            get(AgendamentoRepositoryInterface::class),
            get(VeiculoRepositoryInterface::class),
            get(EstimateService::class),
            get(WhatsAppService::class)
        ),

    // ─── Controllers ───
    ConfigController::class => create(ConfigController::class),

    PlacesController::class => create(PlacesController::class)
        ->constructor(get(PlacesService::class)),

    VehicleController::class => create(VehicleController::class)
        ->constructor(get(VeiculoRepositoryInterface::class)),

    EstimateController::class => create(EstimateController::class)
        ->constructor(get(EstimateService::class), get(WhatsAppService::class)),

    BookingController::class => create(BookingController::class)
        ->constructor(get(BookingService::class)),
];
