<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

class Router
{
    private array $routes = [];

    public function get(string $uri, callable|array $action): void
    {
        $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, callable|array $action): void
    {
        $this->addRoute('POST', $uri, $action);
    }

    // You can add put, delete, etc., as needed.
    
    private function addRoute(string $method, string $uri, callable|array $action): void
    {
        // Converte {param} para regex: [^/]+
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $uri);
        $pattern = "#^" . $pattern . "$#";
        
        $this->routes[$method][$pattern] = $action;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        $routes = $this->routes[$method] ?? [];
        
        foreach ($routes as $pattern => $action) {
            if (preg_match($pattern, $uri, $matches)) {
                // Remove chaves numéricas do preg_match para deixar apenas os parâmetros nomeados
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Adiciona parâmetros ao Request (opcional, aqui injetamos na ação)
                $this->executeAction($action, $request, $params);
                return;
            }
        }

        JsonResponse::error('Rota não encontrada: ' . $method . ' ' . $uri, 404);
    }

    private function executeAction(callable|array $action, Request $request, array $params): void
    {
        if (is_callable($action)) {
            call_user_func($action, $request, $params);
            return;
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $methodName] = $action;
            if (is_object($controller)) {
                $controller->$methodName($request, $params);
            } else {
                JsonResponse::error("Router Misconfiguration: Expected object instance.", 500);
            }
        }
    }
}
