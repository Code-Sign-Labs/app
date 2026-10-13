<?php
declare(strict_types=1);
namespace Framework\Kernel\Subscribers;

use Framework\Addons\AddonsManager;
use Framework\Addons\Prometheus\MetricRegistry;
use Framework\Addons\Prometheus\Manifest as PrometheusManifest;
use Framework\Addons\Prometheus\PrometheusEventsHandler;
use Framework\Core\Container\Container;
use Framework\Core\Container\DependencyInjection;
use Framework\Core\EventManager;
use Framework\Kernel\KernelContext;
use Framework\Kernel\KernelEventSubscriberInterface;

class AddonsSubscriber implements KernelEventSubscriberInterface
{
    public function __construct(
        protected Container $container,
        protected DependencyInjection $dependencyInjection,
    ) {
    }

    public function subscribe(EventManager $eventManager): void
    {
        $eventManager->registerEvent('kernel.configure_addons', function (array $data) {
            $addonsManager = new AddonsManager($this->container);
            $addonsManager->initializeAddons();
            $this->container = $addonsManager->getContainer();

            $this->container->bind(AddonsManager::class, function () use ($addonsManager) {
                return $addonsManager;
            });
            foreach ($addonsManager->getAddons() as $addon) {
                $this->container->bind(get_class($addon), function () use ($addon) {
                    return $addon;
                });
            }

            $eventManager = $this->container->resolve(EventManager::class);
            $eventManager->loadEventsHandler($this->dependencyInjection->build(PrometheusEventsHandler::class));

            $isPrometheusEnabled = $addonsManager->isAddonEnabled('Prometheus');
            if ($isPrometheusEnabled) {
                $this->container->resolve(MetricRegistry::class);
                $manifest = $this->container->resolve(PrometheusManifest::class);
            }

            /** @var KernelContext|null $kernelContext */
            $kernelContext = $data['kernelContext'] ?? null;
            if ($kernelContext instanceof KernelContext) {
                $kernelContext->set('addonsManager', $addonsManager);
                $kernelContext->set('isPrometheusEnabled', $isPrometheusEnabled);
                if (isset($manifest)) {
                    $kernelContext->set('prometheusManifest', $manifest);
                }
            }

            if (isset($data['set'])) {
                $data['set']('addonsManager', $addonsManager);
                $data['set']('isPrometheusEnabled', $isPrometheusEnabled);
                if (isset($manifest)) {
                    $data['set']('prometheusManifest', $manifest);
                }
            }
        });
    }
}

