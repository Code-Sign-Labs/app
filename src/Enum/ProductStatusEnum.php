<?php
declare(strict_types=1);
namespace App\Enum;

enum ProductStatusEnum: string
{
    case active = "active";
    case archived = "archived";
}
