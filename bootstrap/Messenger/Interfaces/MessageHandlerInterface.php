<?php
declare(strict_types=1);
namespace Framework\Messenger\Interfaces;

use Framework\Messenger\Objects\Envelope;

interface MessageHandlerInterface
{
    public function handle(Envelope $envelope): bool;
}