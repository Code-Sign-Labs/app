<?php
declare(strict_types=1);
namespace Framework\Kernel;

use Framework\Core\EventManager;

interface KernelEventSubscriberInterface
{
    public function subscribe(EventManager $eventManager): void;
}
