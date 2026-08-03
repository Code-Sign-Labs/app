<?php

namespace Framework\Security\Hashing;

use Framework\Security\HashingInterface;

class Sha1 implements HashingInterface
{
    /**
     * @param mixed $data
     * @param bool $binary
     * @return string
     */
    public static function hash(mixed $data, bool $binary = false): string
    {
        return sha1($data, $binary);
    }
}