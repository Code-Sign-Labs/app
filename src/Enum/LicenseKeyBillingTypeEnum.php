<?php
declare(strict_types=1);

namespace App\Enum;

enum LicenseKeyBillingTypeEnum: string
{
    case day = 'day';
    case week = 'week';
    case month = 'month';
    case year = 'year';
}
