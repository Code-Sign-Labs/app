<?php
declare(strict_types=1);
namespace Framework\Security\Encryption;

class XOREncryption
{
    /**
     * @param string $data
     * @param string $key
     * @return string
     */
    public static function encrypt(string $data, string $key): string
    {
        $key = str_split($key);
        $data = str_split($data);
        foreach ($data as $i => $char) {
            $data[$i] = chr(ord($char) ^ ord($key[$i % count($key)]));
        }
        return implode('', $data);
    }

    /**
     * @param string $data
     * @param string $key
     * @return string
     */
    public static function decrypt(string $data, string $key): string
    {
        // XOR Encryption is symmetric
        return self::encrypt($data, $key);
    }
}