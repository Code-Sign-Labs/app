<?php
declare(strict_types=1);
namespace Framework\Security\Utils;

class Helpers
{
    /**
     * @param string $data
     * @return string
     */
    public static function sanitize(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}