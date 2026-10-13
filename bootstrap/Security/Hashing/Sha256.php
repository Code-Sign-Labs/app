<?php
declare(strict_types=1);
namespace Framework\Security\Hashing;

use Framework\Security\HashingInterface;

class Sha256 implements HashingInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public static function hash(mixed $data): string
    {
        return hash('sha256', $data);
    }
}