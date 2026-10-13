<?php
declare(strict_types=1);
namespace Framework\Core;

interface EventHandlerInterface
{
    public function handle(array $data): void;
}