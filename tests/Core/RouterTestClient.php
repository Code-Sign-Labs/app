<?php

namespace Tests\Core;

use Framework\Core\Container\Container;
use Framework\Http\Interfaces\FirewallInterface;
use Framework\Http\Interfaces\MiddlewareInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Router\RouteTable;
use Framework\Router\Router;
use RuntimeException;

class RouterTestClient extends Router
{
    public function __construct(RouteTable $routeTable, Container $container, int $appMode)
    {
        parent::__construct($routeTable, $container, $appMode);
    }

    public function request(
        string $method,
        string $uri,
        array $headers = [],
        array $query = [],
        array $body = [],
        array $cookies = [],
        array $files = []
    ): Response {
        $request = new Request($headers, $query, $body, $cookies, $files, [], $uri);
        $routes = $this->getAllEndpointByMethod($method);

        $notFoundController = $this->dependencyInjection->build($this->routeTable->getNotFoundController());
        if (empty($routes)) {
            return $notFoundController->handle($request);
        }

        foreach ($routes as $route) {
            $matches = $this->matchEndpoint($uri, $route->getEndpoint());
            if ($matches === null) {
                continue;
            }

            $request->setParams($matches);
            $firewallName = $route->getFirewall();
            if ($firewallName) {
                $firewall = $this->dependencyInjection->build($firewallName);
                if ($firewall instanceof FirewallInterface && !$firewall->handle($request)) {
                    return $firewall->onFailure();
                }
            }

            $controllerString = $route->getController();
            [$controllerClass, $methodName] = explode('@', $controllerString);

            $controller = $this->dependencyInjection->build($controllerClass);

            $finalHandler = function (Request $request) use ($controller, $methodName) {
                if (!method_exists($controller, $methodName)) {
                    throw new RuntimeException("Method {$methodName} not found in controller " . get_class($controller));
                }

                return call_user_func([$controller, $methodName], $request);
            };

            $middlewares = $route->getMiddlewares();
            if (!empty($middlewares)) {
                $pipeline = array_reduce(
                    array_reverse($middlewares),
                    function ($next, $middlewareClass) {
                        return function (Request $request) use ($middlewareClass, $next) {
                            $middleware = $this->dependencyInjection->build($middlewareClass);

                            if (!$middleware instanceof MiddlewareInterface) {
                                throw new RuntimeException("Middleware {$middlewareClass} must implement MiddlewareInterface");
                            }

                            return $middleware->handle($request, $next);
                        };
                    },
                    $finalHandler
                );

                return $pipeline($request);
            }

            return $finalHandler($request);
        }

        return $notFoundController->handle($request);
    }
}

