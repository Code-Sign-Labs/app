<?php

namespace Framework\Kernel;

use Framework\Core\EventManager;

interface KernelEventSubscriberInterface
{
    public function subscribe(EventManager $eventManager): void;
}
