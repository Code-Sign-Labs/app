<?php

namespace App\Enum;

enum LicenseKeyTypeEnum: string
{
    case perpetual = "perpetual";
    case subscription = "subscription";
    case trial = "trial";
}
