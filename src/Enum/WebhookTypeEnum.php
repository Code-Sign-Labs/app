<?php
declare(strict_types=1);
namespace App\Enum;

enum WebhookTypeEnum: string
{
    case DISCORD_WEBHOOK = 'discord_webhook';
    case RAW_WEBHOOK = 'raw_webhook';
    case SLACK_WEBHOOK = 'slack_webhook';
    case TELEGRAM_WEBHOOK = 'telegram_webhook';
    case ZAPIER_WEBHOOK = 'zapier_webhook';
    case MICROSOFT_TEAMS_WEBHOOK = 'microsoft_teams_webhook';
    case N8N_WEBHOOK = 'n8n_webhook';
    case WHATSAPP_WEBHOOK = 'whatsapp_webhook';
}
