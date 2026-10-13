<?php
declare(strict_types=1);
namespace App\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class StringExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'shorten',
                function (string $text, int $length = 50, string $suffix = '...') {
                    if (strlen($text) <= $length) {
                        return $text;
                    }

                    return substr($text, 0, $length) . $suffix;
                }
            ),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'shorten',
                function (string $text, int $length = 50, string $suffix = '...') {
                    if (strlen($text) <= $length) {
                        return $text;
                    }

                    return substr($text, 0, $length) . $suffix;
                }
            ),
        ];
    }
}
