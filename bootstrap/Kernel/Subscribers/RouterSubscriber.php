<?php

namespace Framework\Kernel\Subscribers;

use Framework\Addons\Prometheus\Controller as PrometheusController;
use Framework\Router\RouteTable;
use Framework\Router\Router;
use Framework\Core\Container\Container;
use Framework\Core\EventManager;
use Framework\Kernel\KernelContext;
use Framework\Kernel\KernelEventSubscriberInterface;
use Framework\Kernel\KernelOptions;

class RouterSubscriber implements KernelEventSubscriberInterface
{
    public function __construct(
        protected Container $container,
        protected KernelOptions $options,
    ) {
    }

    public function subscribe(EventManager $eventManager): void
    {
        $eventManager->registerEvent('kernel.configure_router', function (array $data) {
            /** @var RouteTable $routeTable */
            $routeTable = $data['routeTable'];

            // prom metrics route
            $isPrometheusEnabled = $data['isPrometheusEnabled'] ?? false;
            $manifest = $data['prometheusManifest'] ?? null;
            if ($isPrometheusEnabled && $manifest) {
                $routeTable->addRoute(
                    $manifest->getConfig('endpoint') ?? '/metrics',
                    $manifest->getConfig('method') ?? 'GET',
                    PrometheusController::class . '@metrics',
                    firewall: $manifest->getConfig('firewall') ?? null
                );
            }

            if ($this->options->debugBar) {
                $this->options->debugBar['messages']->addMessage('Router started', 'debug');
            }

            $router = new Router($routeTable, $this->container, $this->options->appMode);

            /** @var KernelContext|null $kernelContext */
            $kernelContext = $data['kernelContext'] ?? null;
            if ($kernelContext instanceof KernelContext) {
                $kernelContext->set('router', $router);
            }

            if (isset($data['set'])) {
                $data['set']('router', $router);
            }
        });
    }
}
