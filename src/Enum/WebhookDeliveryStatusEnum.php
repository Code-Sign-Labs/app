<?php
declare(strict_types=1);
namespace App\Enum;

enum WebhookDeliveryStatusEnum: string
{
    case success = "success";
    case failure = "failure";
    case error = "error";
    case await_delivery = "await_delivery";
}
