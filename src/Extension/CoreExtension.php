<?php
declare(strict_types=1);
namespace App\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CoreExtension extends AbstractExtension
{
    public string $DOMAIN = '';

    public function __construct()
    {
        $this->DOMAIN = $_ENV["APP_URL"];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'domain',
                function (): string {
                    return $this->DOMAIN;
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
            new TwigFunction(
                "version",
                function (): string {
                    return file_get_contents(__DIR__ . "/../../VERSION");
                }
            )
        ];
    }
}
