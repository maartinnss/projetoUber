<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Respect\Validation\Validator as v;

class WhatsApp
{
    private string $value;

    public function __construct(string $value)
    {
        $clean = preg_replace('/[^0-9]/', '', $value);
        
        // Validação básica de telefone brasileiro: pelo menos 10-11 dígitos
        if (!v::stringType()->length(10, 15)->validate($clean)) {
            throw new \InvalidArgumentException('Número de WhatsApp inválido. Informe o DDD e o número.');
        }

        $this->value = $clean;
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
