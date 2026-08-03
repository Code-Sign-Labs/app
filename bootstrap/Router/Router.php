<?php

namespace Framework\Router;

use DebugBar\JavascriptRenderer;
use Exception;
use Framework\AppEnum;
use Framework\Core\Container\Container;
use Framework\Core\Container\DependencyInjection;
use Framework\Core\EventManager;
use Framework\Http\Interfaces\FirewallInterface;
use Framework\Http\Interfaces\MiddlewareInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use ReflectionException;
use RuntimeException;

class Router
{
    /**
     * @var DependencyInjection $dependencyInjection
     */
    protected DependencyInjection $dependencyInjection;

    /**
     * @var Route $currentRoute
     */
    protected Route $currentRoute;

    /**
     * @var EventManager $eventManager
     */
    protected EventManager $eventManager;

    /**
     * @param RouteTable $routeTable
     * @param Container $container
     * @param int $APP_MODE
     * @throws Exception
     */
    public function __construct(protected RouteTable $routeTable, protected Container $container, protected int $APP_MODE)
    {
        $this->eventManager = $this->container->resolve(EventManager::class);
        $this->dependencyInjection = new DependencyInjection($this->container);
    }

    /**
     * @param string $requestMethod
     * @param string $requestUri
     * @param bool $returnResponse false
     * @param Request|null $mockRequest null
     * @return ?Response
     * @throws ReflectionException
     */
    public function handle(string $requestMethod, string $requestUri, bool $returnResponse = false, ?Request $mockRequest = null): ?Response
    {
        $request = $mockRequest ?? new Request();
        $routes = $this->getAllEndpointByMethod($requestMethod);

        $notFoundController = $this->routeTable->getNotFoundController();
        $notFoundController = $this->dependencyInjection->build($notFoundController);
        if (empty($routes)) {

            $this->eventManager->triggerEvent('request.failed', [
                'container' => $this->container,
                'router' => $this,
                'reason' => 'no_routes_found',
                'method' => $requestMethod,
                'uri' => $requestUri,
            ]);

            $response = $notFoundController->handle($request);
            if($returnResponse) return $response;
            $this->sendResponse($response);
            return null;
        }

        foreach ($routes as $route) {
            $matches = $this->matchEndpoint($requestUri, $route->getEndpoint());

            if ($matches !== null) {
                $request->setParams($matches);
                $firewallName = $route->getFirewall();

                if ($firewallName) {
                    $firewall = $this->dependencyInjection->build($firewallName);
                    if ($firewall instanceof FirewallInterface && !$firewall->handle($request)) {
                        $this->eventManager->triggerEvent('request.failed', [
                            'container' => $this->container,
                            'router' => $this,
                            'reason' => 'firewall_blocked',
                            'method' => $requestMethod,
                            'uri' => $requestUri,
                            'firewall' => $firewallName,
                        ]);
                        $response = $firewall->onFailure();
                        if($returnResponse) return $response;
                        $this->sendResponse($response);
                        return null;
                    }
                }

                $controllerString = $route->getController();
                list($controllerClass, $method) = explode('@', $controllerString);

                $controller = $this->dependencyInjection->build($controllerClass);

                $finalHandler = function(Request $request) use ($controller, $method) {
                    if (!method_exists($controller, $method)) {
                        throw new RuntimeException("Method $method not found in controller " . get_class($controller));
                    }
                    return call_user_func([$controller, $method], $request);
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

                    $response = $pipeline($request);
                } else {
                    $response = $finalHandler($request);
                }

                $this->currentRoute = $route;
                if($returnResponse) return $response;
                $this->sendResponse($response);
                return null;
            }
        }

        $this->eventManager->triggerEvent('request.failed', [
            'container' => $this->container,
            'router' => $this,
            'reason' => 'no_matching_route',
            'method' => $requestMethod,
            'uri' => $requestUri,
        ]);

        $response = $notFoundController->handle($request);

        if($returnResponse) return $response;
        $this->sendResponse($response);
        return null;
    }

    /**
     * Match an endpoint, ignoring query parameters.
     *
     * @param string $requestUri
     * @param string $pattern
     * @return array|null
     */
    protected function matchEndpoint(string $requestUri, string $pattern): ?array
    {
        $parsedUrl = parse_url($requestUri);
        $path = $parsedUrl['path'] ?? '/';

        if (preg_match($pattern, $path, $matches)) {
            return array_filter($matches, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    /***
     * @param Response $response
     * @return void
     * @throws Exception
     */
    protected function sendResponse(Response $response): void
    {
        // Event: response about to be sent
        $this->eventManager->triggerEvent('response.sent', [
            'container' => $this->container,
            'router' => $this,
            'status' => (string)$response->getStatusCode(),
            'headers' => $response->getHeaders(),
        ]);

        http_response_code($response->getStatusCode());
        foreach($response->getHeaders() as $header => $value) {
            header("$header: $value");
        }
        echo $response->getBody();
    }

    /**
     * @param string $method
     * @return Route[]
     */
    protected function getAllEndpointByMethod(string $method): array
    {
        $endpoints = [];
        foreach($this->routeTable->getRoutes() as $route) {
            if(str_contains($route->getMethod(), ",")) {
                $methods = explode(",", $route->getMethod());
                foreach($methods as $m) {
                    if($m === $method) {
                        $endpoints[] = $route;
                    }
                }
            } else {
                if($route->getMethod() === $method) {
                    $endpoints[] = $route;
                }
            }
        }

        return $endpoints;
    }

    /**
     * @return Route
     */
    public function getCurrentRoute(): Route
    {
        return $this->currentRoute;
    }
}