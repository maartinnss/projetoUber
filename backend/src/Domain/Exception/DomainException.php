<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

class DomainException extends RuntimeException
{
    protected int $statusCode = 400;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
