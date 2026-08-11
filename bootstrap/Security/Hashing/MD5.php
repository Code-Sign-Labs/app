<?php
declare(strict_types=1);
namespace Framework\Security\Hashing;

use Framework\Security\HashingInterface;

class MD5 implements HashingInterface
{
    /**
     * @note This function should not be used to hash some important data because algorithm is very weak!!
     * @deprecated
     * @param string $data
     * @return string
     */
    public static function hash(mixed $data): string
    {
        return md5($data);
    }
}