<?php

namespace Framework\Core;

interface EventsHandlerInterface
{
    /**
     * @return array<string, string>
     */
    public function getHandlers(): array;
}