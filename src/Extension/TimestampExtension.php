<?php
declare(strict_types=1);
namespace App\Extension;

use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TimestampExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'format_timestamp',
                function (DateTime $dateTime) {
                    return $dateTime->format('Y-m-d H:i:s');
                }
            ),
            new TwigFunction(
                'time_ago',
                function (DateTime $dateTime) {
                    $now  = new DateTime();
                    $diff = $now->getTimestamp() - $dateTime->getTimestamp();

                    if ($diff < 60) {
                        return 'just now';
                    } elseif ($diff < 3600) {
                        return floor($diff / 60) . ' minutes ago';
                    } elseif ($diff < 86400) {
                        return floor($diff / 3600) . ' hours ago';
                    } else {
                        return floor($diff / 86400) . ' days ago';
                    }
                }
            ),
        ];
    }
}
