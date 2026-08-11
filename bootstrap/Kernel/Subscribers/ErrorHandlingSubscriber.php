<?php
declare(strict_types=1);
namespace Framework\Kernel\Subscribers;

use Framework\Configs\ConfigCore;
use Framework\Core\Container\Container;
use Framework\Core\EventManager;
use Framework\Error\AppErrorHandler;
use Framework\Kernel\KernelEventSubscriberInterface;
use Framework\AppEnum;
use Framework\Kernel\KernelOptions;

class ErrorHandlingSubscriber implements KernelEventSubscriberInterface
{
    public function __construct(protected ConfigCore $configCore, protected Container $container)
    {
    }

    public function subscribe(EventManager $eventManager): void
    {
        $eventManager->registerEvent('kernel.configure_error_handling', function () {
            // If error config is not present, do nothing
            if (!isset($this->configCore->getConfigs()['error'])) {
                return;
            }

            // When running in testing mode we want exceptions to bubble up to the test runner
            // so the TestsManager can catch them and present clean output. Do not install
            // the global error/exception handlers in that case.
            try {
                if ($this->container->has(KernelOptions::class)) {
                    $options = $this->container->resolve(KernelOptions::class);
                    if ($options->appMode === AppEnum::APP_MODE_TESTING) {
                        return;
                    }
                }
            } catch (\Throwable) {
                // ignore and continue to set handlers
            }

            // Install global handlers for non-testing modes
            set_error_handler(AppErrorHandler::class . '::staticHandleError');

            $appErrorHandler = new AppErrorHandler($this->configCore, $this->container);

            set_exception_handler(function ($exception) use ($appErrorHandler) {
                $appErrorHandler->handleException($exception);
            });

            set_error_handler(function (...$args) use ($appErrorHandler) {
                var_dump($args);
                $appErrorHandler->handleError(...$args);
            });
        });
    }
}

