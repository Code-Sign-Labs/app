<?php
declare(strict_types=1);
namespace Framework\Security\Encryption;

class Base64
{
    /**
     * @param string $data
     * @return string
     */
    public static function encrypt(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * @param string $data
     * @return string
     */
    public static function decrypt(string $data): string
    {
        return base64_decode($data);
    }
}