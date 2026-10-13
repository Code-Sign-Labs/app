<?php
declare(strict_types=1);
namespace Framework\Security;

interface HashingInterface
{
    public static function hash(mixed $data): string;
}