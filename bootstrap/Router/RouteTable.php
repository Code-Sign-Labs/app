<?php

namespace Framework\Router;

use Framework\Http\Interfaces\FirewallInterface;
use Framework\Http\Interfaces\MiddlewareInterface;
use Framework\Http\Interfaces\NotFoundControllerInterface;

class RouteTable
{
    /**
     * @var Route[] $routes
     */
    protected array $routes = [];

    protected string $notFoundController;

    /**
     * @param string $endpoint
     * @param string $method
     * @param string $controller
     * @param ?array $middlewares
     * @param string|null $firewall
     * @return void
     */
    public function addRoute(string $endpoint, string $method, string $controller, ?array $middlewares = null, string $firewall = null): void
    {
        $endpoint = preg_replace('/<([A-Za-z_][A-Za-z0-9_]*)>/', '(?P<$1>[^/]+)', $endpoint);
        $this->routes[] = new Route("#^{$endpoint}$#", $method, $controller, $middlewares, $firewall);
    }

    /**
     * @param string $notFoundController
     */
    public function setNotFoundController(string $notFoundController): void
    {
        $this->notFoundController = $notFoundController;
    }

    /**
     * @return string
     */
    public function getNotFoundController(): string
    {
        return $this->notFoundController;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}