<?php

namespace Framework\Utils;

use Random\RandomException;

class Helpers
{
    /**
     * @param string $email
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @param int $length
     * @return string
     * @throws RandomException
     */
    public static function randomString(int $length = 10): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * @param string $string
     * @return string
     */
    public function humanize(string $string): string
    {
        return ucwords(str_replace('_', ' ', $string));
    }

    /**
     * @param  string $string
     * @return string
     */
    public static function snakeCase(string $string): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', lcfirst($string)));
    }

    /**
     * @param  string $string
     * @return string
     */
    public static function camelCase(string $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

    /**
     * @param  string $string
     * @return string
     */
    public static function titleCase(string $string): string
    {
        return ucwords($string);
    }
}