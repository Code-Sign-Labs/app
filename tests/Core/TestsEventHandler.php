<?php
declare(strict_types=1);
namespace Tests\Core;

use Framework\Core\EventsHandlerInterface;
use Framework\Kernel\KernelEvents;

class TestsEventHandler implements EventsHandlerInterface
{
    public function getHandlers(): array
    {
        return [
            KernelEvents::HANDLE_TESTS => \Tests\Core\Events\InitializeTestsEvent::class
        ];
    }
}