<?php

namespace Framework\Security\Hashing;

class HMAC
{
    /**
     * @param mixed $data
     * @param string $secretKey
     * @param string $algorithm
     * @param bool $binary
     * @return string
     */
    public static function hash(mixed $data, string $secretKey, string $algorithm = 'sha256', bool $binary = false): string
    {
        return hash_hmac($algorithm, $data, $secretKey, $binary);
    }
}