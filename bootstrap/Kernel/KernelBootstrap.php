<?php
declare(strict_types=1);
namespace Framework\Kernel;

use Framework\Core\Container\Container;
use Framework\Core\Container\DependencyInjection;
use Framework\Core\EventManager;
use Framework\Kernel\KernelEventSubscriberInterface;

class KernelBootstrap
{
    /**
     * @var KernelEventSubscriberInterface[]
     */
    private array $subscribers = [];

    public function __construct(
        private readonly Container $container,
        private readonly DependencyInjection $di,
        private readonly EventManager $eventManager,
    ) {
    }

    public function addSubscriber(KernelEventSubscriberInterface $subscriber): self
    {
        $this->subscribers[] = $subscriber;
        return $this;
    }

    public function boot(): void
    {
        foreach ($this->subscribers as $subscriber) {
            $subscriber->subscribe($this->eventManager);
        }
    }
}
