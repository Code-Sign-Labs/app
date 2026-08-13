<?php
declare(strict_types=1);
namespace App\Enum;

enum LicenseKeyTypeEnum: string
{
    case perpetual = "perpetual";
    case subscription = "subscription";
    case trial = "trial";
}
