<?php

namespace Framework\Kernel\Subscribers;

use Framework\AppEnum;
use Framework\Configs\ConfigCore;
use Framework\Core\Container\Container;
use Framework\Core\Container\DependencyInjection;
use Framework\Core\EventManager;
use Framework\Debug\TwigDebugExtension;
use Framework\Http\ViewEngine\Engines\TwigViewEngine;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Framework\Http\ViewEngine\ViewFactory;
use Framework\Kernel\KernelEventSubscriberInterface;
use Framework\Kernel\KernelOptions;
use Twig\Extension\AbstractExtension;

class ViewEngineSubscriber implements KernelEventSubscriberInterface
{
    public function __construct(
        protected ConfigCore $configCore,
        protected Container $container,
        protected DependencyInjection $dependencyInjection,
        protected KernelOptions $options,
    ) {
    }

    public function subscribe(EventManager $eventManager): void
    {
        $eventManager->registerEvent('kernel.configure_view_engine', function () {
            $viewConfig = $this->configCore->getConfig('render');
            if (!$viewConfig->get('view.use')) {
                return;
            }

            $mode = $viewConfig->get('view.engine');
            $engine = ViewFactory::create($mode, array_merge(
                ['viewsPath' => $viewConfig->get('view.path')],
                $viewConfig->get("view.$mode.options")
            ));

            $debugBar = $this->options->debugBar;
            if ($debugBar) {
                $debugBar['messages']->addMessage('Starting ' . ucfirst($mode) . ' view engine', 'debug');
            }

            // Twig extensions + debug extension
            if ($engine instanceof TwigViewEngine) {
                $extensionsPath = $this->configCore->getConfig('namespace')->get('namespace.extensions.path');
                $namespace = $this->configCore->getConfig('namespace')->get('namespace.extensions.namespace');

                if ($extensionsPath) {
                    foreach (glob($extensionsPath) as $extension) {
                        $className = str_replace('.php', '', basename($extension));
                        $fullClassName = $namespace . '\\' . $className;
                        if (class_exists($fullClassName)) {
                            $instance = $this->dependencyInjection->build($fullClassName);
                            if ($instance instanceof AbstractExtension) {
                                $engine->getTwig()->addExtension($instance);
                            }
                        }
                    }
                }

                if ($this->options->appMode == AppEnum::APP_MODE_DEBUG && $debugBar && $this->options->debugRenderer) {
                    $engine->getTwig()->addExtension(new TwigDebugExtension($this->options->debugRenderer));
                }

                if ($debugBar) {
                    $debugBar['messages']->addMessage('Twig extensions loaded', 'debug');
                }
            }

            $this->container->bind(ViewEngineInterface::class, function () use ($engine) {
                return $engine;
            });

            if ($debugBar) {
                $debugBar['messages']->addMessage('View Engine started', 'debug');
            }
        });
    }
}

