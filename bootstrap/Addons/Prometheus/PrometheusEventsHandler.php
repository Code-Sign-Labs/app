<?php

namespace Framework\Addons\Prometheus;

use Framework\Addons\Prometheus\Handlers\PrometheusRequestCompletedHandler;
use Framework\Core\EventsHandlerInterface;

class PrometheusEventsHandler implements EventsHandlerInterface
{
    /**
     * @var array<string, string> $handlers
     */
    protected array $handlers = [
        'request.completed' => PrometheusRequestCompletedHandler::class,
    ];

    /**
     * @return array
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}