<?php

namespace Framework\Security;

interface HashingVerifyInterface
{
    public static function verify(string $data, string $hashedData): bool;
}