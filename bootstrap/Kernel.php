<?php

namespace Framework;

use Tests\Core\TestsEventHandler;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Framework\Configs\ConfigCore;
use Framework\Core\Container\Container;
use Framework\Core\Container\DependencyInjection;
use Framework\Core\EventManager;
use Framework\Kernel\KernelBootstrap;
use Framework\Kernel\KernelContext;
use Framework\Kernel\KernelEvents;
use Framework\Kernel\KernelOptions;
use Framework\Kernel\Subscribers\AddonsSubscriber;
use Framework\Kernel\Subscribers\DebugSubscriber;
use Framework\Kernel\Subscribers\ErrorHandlingSubscriber;
use Framework\Kernel\Subscribers\OrmSubscriber;
use Framework\Kernel\Subscribers\RequestLifecycleSubscriber;
use Framework\Kernel\Subscribers\RouterSubscriber;
use Framework\Kernel\Subscribers\ViewEngineSubscriber;
use Framework\Router\RouteTable;
use InvalidArgumentException;
use ReflectionException;

class Kernel
{
    /**
     * @var Container $container
     */
    protected Container $container;

    /**
     * @var DependencyInjection $dependencyInjection
     */
    protected DependencyInjection $dependencyInjection;

    /**
     * @var EventManager $eventManager
     */
    protected EventManager $eventManager;

    /**
     * @param string $APP_ROUTING_WEB_PATH
     * @param string $APP_ROUTING_CONSOLE_PATH
     * @param string $APP_ROUTING_HEALTH_ENDPOINT
     * @param string $APP_BASE_PATH
     * @param ConfigCore $configCore
     * @param ?StandardDebugBar $debugBar
     * @param ?JavascriptRenderer $debugRenderer
     * @param int $APP_MODE
     * @param string $APP_NAME
     * @throws ReflectionException
     */
    public function __construct(
        protected string $APP_ROUTING_WEB_PATH,
        protected string $APP_ROUTING_CONSOLE_PATH,
        protected string $APP_ROUTING_HEALTH_ENDPOINT,
        protected string $APP_BASE_PATH,
        protected ConfigCore $configCore,
        protected int $APP_MODE,
        protected string $APP_NAME = '',
        protected ?StandardDebugBar $debugBar = null,
        protected ?JavascriptRenderer $debugRenderer = null
    ) {
        $this->container = new Container();
        $this->dependencyInjection = new DependencyInjection($this->container);

        $this->container->singleton(EventManager::class, function () {
            return new EventManager($this->container);
        });
        $this->eventManager = $this->container->resolve(EventManager::class);
        if($this->APP_MODE === AppEnum::APP_MODE_TESTING) {
            $this->eventManager->loadEventsHandler(
                new TestsEventHandler()
            );
        }

        // Register ConfigCore as a singleton so DI will always resolve the provided
        // ConfigCore instance rather than attempting to build it via reflection.
        $this->container->singleton(ConfigCore::class, function () {
            return $this->configCore;
        });

        $this->container->bind(KernelOptions::class, function () {
            return new KernelOptions(
                appMode: $this->APP_MODE,
                appName: $this->APP_NAME,
                basePath: $this->APP_BASE_PATH,
                debugBar: $this->debugBar,
                debugRenderer: $this->debugRenderer,
            );
        });

        $kernelContext = new KernelContext();
        $set = function (string $key, mixed $value) use ($kernelContext) {
            $kernelContext->set($key, $value);
        };

        $bootstrap = new KernelBootstrap($this->container, $this->dependencyInjection, $this->eventManager);
        $bootstrap
            ->addSubscriber($this->dependencyInjection->build(ErrorHandlingSubscriber::class))
            ->addSubscriber($this->dependencyInjection->build(DebugSubscriber::class))
            ->addSubscriber($this->dependencyInjection->build(ViewEngineSubscriber::class))
            ->addSubscriber($this->dependencyInjection->build(OrmSubscriber::class));
        $bootstrap
            ->addSubscriber($this->dependencyInjection->build(AddonsSubscriber::class))
            ->addSubscriber($this->dependencyInjection->build(RequestLifecycleSubscriber::class))
            ->addSubscriber($this->dependencyInjection->build(RouterSubscriber::class));

        $bootstrap->boot();

        $this->eventManager->triggerEvent(KernelEvents::BOOT, [
            'container' => $this->container,
            'kernelContext' => $kernelContext,
            'appName' => $this->APP_NAME,
            'appMode' => $this->APP_MODE,
            'basePath' => $this->APP_BASE_PATH,
            'timeFloat' => $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true),
        ]);

        $this->eventManager->triggerEvent(KernelEvents::CONFIGURE_ADDONS, [
            'container' => $this->container,
            'kernelContext' => $kernelContext,
            'set' => $set,
        ]);
        $this->eventManager->triggerEvent(KernelEvents::CONFIGURE_ERROR_HANDLING, [
            'container' => $this->container,
        ]);
        $this->eventManager->triggerEvent(KernelEvents::CONFIGURE_DEBUG, [
            'container' => $this->container,
        ]);
        $this->eventManager->triggerEvent(KernelEvents::CONFIGURE_VIEW_ENGINE, [
            'container' => $this->container,
        ]);
        $this->eventManager->triggerEvent(KernelEvents::CONFIGURE_ORM, [
            'container' => $this->container,
        ]);

        // Load routing table and configure router (also required for tests)
        $routeTable = require $this->APP_ROUTING_WEB_PATH;
        if (!($routeTable instanceof RouteTable)) {
            throw new InvalidArgumentException('Route table must be an instance of RouteTable');
        }

        $this->eventManager->triggerEvent(KernelEvents::CONFIGURE_ROUTER, array_merge($kernelContext->all(), [
            'container' => $this->container,
            'kernelContext' => $kernelContext,
            'routeTable' => $routeTable,
            'set' => $set,
        ]));

        $router = $kernelContext->get('router');
        if (!$router) {
            throw new InvalidArgumentException('Router was not configured. Missing "router" in kernel context.');
        }

        // In testing mode—pass router to tests handler so tests can query API directly
        if($this->APP_MODE === AppEnum::APP_MODE_TESTING) {
            $this->eventManager->triggerEvent(KernelEvents::HANDLE_TESTS, [
                'container' => $this->container,
                'kernelContext' => $kernelContext,
                'router' => $router,
            ]);
            return;
        }

        // Normal runtime: handle incoming request
        $this->eventManager->triggerEvent(KernelEvents::HANDLE_REQUEST, [
            'container' => $this->container,
            'kernelContext' => $kernelContext,
            'router' => $router,
        ]);
    }
}

