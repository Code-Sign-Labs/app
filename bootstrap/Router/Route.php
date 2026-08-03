<?php

namespace Framework\Router;


class Route
{
    public function __construct(protected string $endpoint, protected string $method, protected string $controller, protected ?array $middlewares = null, protected ?string $firewall = null)
    {
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     * @return Route
     */
    public function setEndpoint(string $endpoint): Route
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return Route
     */
    public function setMethod(string $method): Route
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     * @return Route
     */
    public function setController(string $controller): Route
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getMiddlewares(): ?array
    {
        return $this->middlewares;
    }

    /**
     * @param array|null $middlewares
     * @return Route
     */
    public function setMiddlewares(?array $middlewares): Route
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirewall(): ?string
    {
        return $this->firewall;
    }

    /**
     * @param string|null $firewall
     * @return Route
     */
    public function setFirewall(?string $firewall): Route
    {
        $this->firewall = $firewall;
        return $this;
    }
}