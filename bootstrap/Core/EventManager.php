<?php
declare(strict_types=1);
namespace Framework\Core;

use Framework\Core\Container\Container;
use Framework\Core\Container\DependencyInjection;
use ReflectionException;

class EventManager
{
    /**
     * @var array<callable> $events
     */
    protected array $events = [];

    /**
     * @var DependencyInjection $dependencyInjection
     */
    protected DependencyInjection $dependencyInjection;

    public function __construct(protected Container $container)
    {
        $this->dependencyInjection = new DependencyInjection($this->container);
    }

    /**
     * @param string $eventName
     * @param callable $callback
     * @return void
     */
    public function registerEvent(string $eventName, callable $callback): void
    {
        if (!isset($this->events[$eventName])) {
            $this->events[$eventName] = [];
        }
        $this->events[$eventName][] = $callback;
    }

    /**
     * @param string $eventName
     * @param array $data
     * @return void
     */
    public function triggerEvent(string $eventName, array $data = []): void
    {
        if (!isset($this->events[$eventName])) {
            return;
        }

        foreach ($this->events[$eventName] as $callback) {
            call_user_func($callback, $data);
        }
    }

    /**
     * @param EventsHandlerInterface $eventsHandler
     * @return void
     * @throws ReflectionException
     */
    public function loadEventsHandler(EventsHandlerInterface $eventsHandler): void
    {
        $handlers = $eventsHandler->getHandlers();
        foreach ($handlers as $eventName => $handlerClass) {
            $this->registerEvent($eventName, [$this->dependencyInjection->build($handlerClass), 'handle']);
        }
    }
}