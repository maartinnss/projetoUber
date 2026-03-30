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
        $this->routes[$method][$uri] = $action;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        // Check if the route exists
        if (isset($this->routes[$method][$uri])) {
            $action = $this->routes[$method][$uri];

            if (is_callable($action)) {
                call_user_func($action, $request);
            } elseif (is_array($action) && count($action) === 2) {
                // $action = [ControllerInstance, 'methodName']
                [$classOrObject, $methodName] = $action;

                if (is_object($classOrObject)) {
                    $classOrObject->$methodName($request);
                } else {
                    JsonResponse::error("Router Misconfiguration: Expected object instance for route.", 500);
                }
            }
        } else {
            JsonResponse::error('Rota não encontrada: ' . $method . ' ' . $uri, 404);
        }
    }
}
