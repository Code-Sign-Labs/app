<?php

namespace App\Enum;

enum LicenseKeyStatusEnum: string
{
    case active = "active";
    case revoked = "revoked";
    case expired = "expired";
    case suspended = "suspended";
}
