<?php

namespace Framework\Security\Hashing;

use Framework\Security\HashingInterface;
use Framework\Security\HashingVerifyInterface;

class Bcrypt implements HashingInterface, HashingVerifyInterface
{
    /**
     * @param string $data
     * @param array $options
     * @return string
     */
    public static function hash(mixed $data, array $options = ['cost' => 13]): string
    {
        return password_hash($data, PASSWORD_BCRYPT, $options);
    }

    /**
     * @param string $data
     * @param string $hashedData
     * @return bool
     */
    public static function verify(string $data, string $hashedData): bool
    {
        return password_verify($data, $hashedData);
    }
}