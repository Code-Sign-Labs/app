<?php
declare(strict_types=1);
namespace Framework\Security;

interface HashingVerifyInterface
{
    public static function verify(string $data, string $hashedData): bool;
}