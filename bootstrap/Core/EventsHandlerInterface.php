<?php
declare(strict_types=1);
namespace Framework\Core;

interface EventsHandlerInterface
{
    /**
     * @return array<string, string>
     */
    public function getHandlers(): array;
}