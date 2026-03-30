<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

class Request
{
    private array $data;

    public function __construct()
    {
        // Parse raw body for JSON requests
        $body = file_get_contents('php://input');
        $json = json_decode($body ?: '', true);

        // Merge query params, post array, and json body
        $this->data = array_merge($_GET, $_POST, is_array($json) ? $json : []);
    }

    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function getUri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]) && $this->data[$key] !== '';
    }
}
