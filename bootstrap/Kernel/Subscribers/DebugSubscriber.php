<?php

namespace Framework\Kernel\Subscribers;

use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Framework\Core\Container\Container;
use Framework\Core\EventManager;
use Framework\Kernel\KernelEventSubscriberInterface;
use Framework\Kernel\KernelOptions;

class DebugSubscriber implements KernelEventSubscriberInterface
{
    public function __construct(
        protected Container $container,
        protected KernelOptions $options,
    ) {
    }

    public function subscribe(EventManager $eventManager): void
    {
        $eventManager->registerEvent('kernel.configure_debug', function () {
            $debugBar = $this->options->debugBar;
            $debugRenderer = $this->options->debugRenderer;

            if ($debugBar) {
                $debugBar['messages']->addMessage('Kernel started', 'debug');
                $this->container->bind(StandardDebugBar::class, function () use ($debugBar) {
                    return $debugBar;
                });
            }

            if ($debugRenderer) {
                $this->container->bind(JavascriptRenderer::class, function () use ($debugRenderer) {
                    return $debugRenderer;
                });
            }
        });
    }
}
