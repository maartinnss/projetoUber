<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Respect\Validation\Validator as v;

class Email
{
    private string $value;

    public function __construct(string $value)
    {
        if (!v::email()->validate($value)) {
            throw new \InvalidArgumentException('E-mail inválido.');
        }

        $this->value = mb_strtolower($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
