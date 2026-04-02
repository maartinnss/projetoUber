<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

class SecurityGuard
{
    /**
     * Verifica se a requisição vem de um domínio permitido.
     * Útil para APIs em servidores sem tokens complexos.
     */
    public function checkOrigin(): bool
    {
        $allowed = getenv('ALLOWED_ORIGIN') ?: $_SERVER['HTTP_HOST'] ?? '';
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';

        if (empty($origin)) {
            // Em ambiente local/docker sem headers, podemos permitir ou não.
            // Para produção, deve ser rígido.
            return true; 
        }

        return str_contains($origin, $allowed);
    }

    /**
     * Validação simples de Token CSRF (Placeholder para futura expansão)
     */
    public function validateToken(?string $token): bool
    {
        // Como o app é stateless (sem session padrão PHP ativa no index.php ainda),
        // uma validação via JWT ou Session seria necessária aqui.
        // Por ora, focaremos no Origin Check que é o "Quick Win".
        return true;
    }
}
