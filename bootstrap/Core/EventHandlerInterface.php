<?php

namespace Framework\Core;

interface EventHandlerInterface
{
    public function handle(array $data): void;
}