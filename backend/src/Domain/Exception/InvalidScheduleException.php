<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class InvalidScheduleException extends DomainException
{
    protected int $statusCode = 422;

    public function __construct(string $message = 'Data ou hora de agendamento reservada em um fuso horário inválido.')
    {
        parent::__construct($message);
    }
}
