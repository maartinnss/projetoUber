<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class VehicleNotFoundException extends DomainException
{
    protected int $statusCode = 404;

    public function __construct(string $message = 'Carro não encontrado ou indisponível.')
    {
        parent::__construct($message);
    }
}
