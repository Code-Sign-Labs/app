<?php

namespace Framework\Security\Encryption;

class RSA
{
    /**
     * @param mixed $data
     * @param string $publicKey
     * @return string
     */
    public static function encrypt(mixed $data, string $publicKey): string
    {
        openssl_public_encrypt($data, $encrypted, $publicKey);
        return $encrypted;
    }

    /**
     * @param mixed $data
     * @param string $privateKey
     * @return ?string
     */
    public static function decrypt(mixed $data, string $privateKey): ?string
    {
        openssl_private_decrypt($data, $decrypted, $privateKey);
        return $decrypted;
    }
}