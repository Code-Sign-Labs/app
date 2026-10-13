<?php
declare(strict_types=1);
namespace Framework\Kernel\Subscribers;

use Framework\Core\Container\Container;
use Framework\Core\EventManager;
use Framework\Http\Objects\RequestContext;
use Framework\Kernel\KernelEventSubscriberInterface;
use Framework\Kernel\KernelOptions;

class RequestLifecycleSubscriber implements KernelEventSubscriberInterface
{
    public function __construct(
        protected Container $container,
        protected KernelOptions $options,
    ) {
    }

    public function subscribe(EventManager $eventManager): void
    {
        $eventManager->registerEvent('kernel.handle_request', function (array $data) use ($eventManager) {
            $router = $data['router'];

            $requestContext = RequestContext::fromGlobals();

            $this->container->bind(RequestContext::class, function () use ($requestContext) {
                return $requestContext;
            });

            $eventManager->triggerEvent('request.context.created', [
                'container' => $this->container,
                'router' => $router,
                'requestContext' => $requestContext,
                'requestId' => $requestContext->requestId,
            ]);

            // request.received
            $eventManager->triggerEvent('request.received', [
                'container' => $this->container,
                'router' => $router,
                'requestContext' => $requestContext,
                'requestId' => $requestContext->requestId,
                'ip' => $requestContext->ip,
                'method' => $requestContext->method,
                'uri' => $requestContext->uri,
                'timeFloat' => $requestContext->startedAtFloat,
            ]);

            $debugBar = $this->options->debugBar;
            if ($debugBar && isset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'])) {
                $debugBar['messages']->addMessage('Handling request. Endpoint: ' . $_SERVER['REQUEST_URI'] . ' | Method: ' . $_SERVER['REQUEST_METHOD'], 'debug');
            }

            $router->handle($requestContext->method, $requestContext->uri);

            // request.completed
            $eventManager->triggerEvent('request.completed', [
                'container' => $this->container,
                'router' => $router,
                'requestContext' => $requestContext,
                'requestId' => $requestContext->requestId,
                'ip' => $requestContext->ip,
                'method' => $requestContext->method,
                'uri' => $requestContext->uri,
                'status' => http_response_code() ? (string) http_response_code() : '200',
                'durationSeconds' => $requestContext->durationSeconds(),
            ]);
        });
    }
}
