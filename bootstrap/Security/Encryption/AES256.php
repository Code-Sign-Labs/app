<?php

namespace Framework\Security\Encryption;

use Framework\Security\Hashing\Sha256;

class AES256
{
    /**
     * @param mixed $data
     * @param string $secretKey
     * @param string|null $secretVi
     * @return string
     */
    public static function encrypt(mixed $data, string $secretKey, string $secretVi = null): string
    {
        $secretKey = Sha256::hash($secretKey);
        $secretVi = substr(Sha256::hash($secretVi), 0, 16);
        return openssl_encrypt($data, 'AES-256-CBC', $secretKey, 0, $secretVi);
    }

    /**
     * @param mixed $data
     * @param string $secretKey
     * @param string|null $secretVi
     * @return string
     */
    public static function decrypt(mixed $data, string $secretKey, string $secretVi = null): string
    {
        $secretKey = Sha256::hash($secretKey);
        $secretVi = substr(Sha256::hash($secretVi), 0, 16);
        return openssl_decrypt($data, 'AES-256-CBC', $secretKey, 0, $secretVi);
    }
}