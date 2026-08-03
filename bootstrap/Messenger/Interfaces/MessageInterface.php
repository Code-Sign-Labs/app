<?php

namespace Framework\Messenger\Interfaces;

use DateTime;

interface MessageInterface
{
    public function getContent(): string;
    public function getAvailable(): DateTime;
}