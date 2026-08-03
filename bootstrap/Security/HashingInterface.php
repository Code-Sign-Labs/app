<?php

namespace Framework\Security;

interface HashingInterface
{
    public static function hash(mixed $data): string;
}