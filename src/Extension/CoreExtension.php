<?php

namespace App\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CoreExtension extends AbstractExtension
{
    public const DOMAIN      = 'example.com';
    public const DASH_DOMAIN = 'dashboard.example.com';

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'domain',
                function (): string {
                    return self::DOMAIN;
                }
            ),
            new TwigFunction(
                'domainDashboard',
                function (): string {
                    return 'https://' . self::DASH_DOMAIN;
                }
            ),
            new TwigFunction(
                'env',
                function (string $key, ?string $default = null): ?string {
                    $value = getenv($key);
                    if ($value === false) {
                        return $default;
                    }

                    return $value;
                }
            ),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('short_string', function ($string, $length = 10) {
                if (strlen($string) <= $length) {
                    return $string;
                }

                return substr($string, 0, $length) . '...';
            }),
        ];
    }
}
