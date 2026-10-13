<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\Webhook;
use App\Enum\EventNameEnum;
use App\Enum\WebhookTypeEnum;
use DateTime;

class WebhookFactory
{
    /**
     * @param array $payload
     * @param string $secret
     * @return string
     */
    public function createSignature(array $payload, string $secret): string
    {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash_hmac(
            'sha256',
            $payloadJson,
            $secret,
            false
        );
    }

    /**
     * @param array $format
     * @param array $data
     * @param EventNameEnum $eventNameEnum
     * @param string $eventId
     * @return array
     */
    protected function useCustomPayloadFormat(array $format, array $data, EventNameEnum $eventNameEnum, string $eventId): array
    {
        $replacements = [
            '{eventName}' => $eventNameEnum->value,
            '{eventId}'   => $eventId,
            '{timestamp}' => time(),
        ];

        array_walk_recursive($format, function (&$value) use ($data, $replacements) {
            if ($value === '{data}') {
                $value = $data;
                return;
            }

            if (is_string($value)) {
                $value = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $value
                );
            }
        });

        return $format;
    }

    /**
     * @param WebhookTypeEnum $type
     * @param array $data
     * @param EventNameEnum $eventName
     * @param Webhook $webhook
     * @param string $id
     * @return array|array[]
     */
    public function create(WebhookTypeEnum $type, array $data, EventNameEnum $eventName, Webhook $webhook, string $id): array
    {
        switch ($type) {
            case WebhookTypeEnum::DISCORD_WEBHOOK:
                $formatted = [];
                foreach ($data as $key => $value) {
                    if(is_array($value) || is_object($value)) {
                        $value = json_encode($value, JSON_PRETTY_PRINT);
                    }
                    $value = (string)$value;
                    $formatted[] = [
                        "name" => "**$key**",
                        "value" => "`$value`",
                    ];
                }

                return [
                    "username" => "Code Sign App BOT",
                    "avatar_url" => "https://avatars.githubusercontent.com/u/311899749",
                    "embeds" => [
                        [
                            "title" => "Code Sign App Webhook",
                            "description" => "**Event:** {$eventName->value}\n**Date:** " . date('Y-m-d H:i:s'),
                            "color" => 16727357,
                            "fields" => $formatted,
                            "author" => [
                                "name" => "Code Sign App",
                                "icon_url" => "https://avatars.githubusercontent.com/u/311899749",
                                "url" => "https://github.com/Code-Sign-Labs/app",
                            ]
                        ]
                    ]
                ];
            case WebhookTypeEnum::RAW_WEBHOOK:
                if($webhook->getCustomPayload()) {
                    return $this->useCustomPayloadFormat(
                        $webhook->getCustomPayload(),
                        $data,
                        $eventName,
                        $id
                    );
                }
                return [
                    "event" => $eventName->value,
                    "data" => $data,
                    "timestamp" => microtime(true),
                    "event_id" => $id,
                    "source" => "codesign"
                ];
            case WebhookTypeEnum::SLACK_WEBHOOK:
                return [
                    "blocks" => [
                        [
                            "type" => "header",
                            "text" => [
                                "type" => "plain_text",
                                "text" => "🔐 Code Sign - " . ucwords(str_replace('_', ' ', $eventName->value)),
                            ],
                        ],
                        [
                            "type" => "section",
                            "text" => [
                                "type" => "mrkdwn",
                                "text" => "```" . $this->formatDataAsText($data) . "```",
                            ],
                        ],
                        [
                            "type" => "divider",
                        ],
                        [
                            "type" => "context",
                            "elements" => [
                                [
                                    "type" => "mrkdwn",
                                    "text" => "_Sent by Code Sign • " . date('c') . "_",
                                ],
                            ],
                        ],
                    ],
                ];
            case WebhookTypeEnum::TELEGRAM_WEBHOOK:
                return [
                    "chat_id" => $webhook->getCustomPayload()['chat_id'] ?? null,
                    "text" => "<b>Code Sign</b>\n<b>Event:</b> " . ucwords(str_replace('_', ' ', $eventName->value)) . "\n\n<pre>" . $this->formatDataAsText($data) . "</pre>\n\n<i>Sent by Code Sign • " . date('c') . "</i>",
                    "parse_mode" => "HTML",
                    "disable_notification" => false,
                ];
            case WebhookTypeEnum::ZAPIER_WEBHOOK:
                return [
                    "brand" => "Code Sign",
                    "event_type" => $eventName->value,
                    "event_name" => ucwords(str_replace('_', ' ', $eventName->value)),
                    "timestamp" => date('c'),
                    "payload" => $data,
                    "webhook_version" => "1.0",
                ];
            case WebhookTypeEnum::MICROSOFT_TEAMS_WEBHOOK:
                $facts = [];
                foreach ($data as $key => $value) {
                    $facts[] = [
                        "name" => ucwords(str_replace('_', ' ', $key)),
                        "value" => is_array($value) ? json_encode($value) : (string)$value,
                    ];
                }
                return [
                    "@type" => "MessageCard",
                    "@context" => "https://schema.org/extensions",
                    "summary" => "Code Sign - " . ucwords(str_replace('_', ' ', $eventName->value)),
                    "themeColor" => "0078D7",
                    "sections" => [
                        [
                            "activityTitle" => "Code Sign",
                            "activitySubtitle" => ucwords(str_replace('_', ' ', $eventName->value)),
                            "facts" => $facts,
                            "markdown" => true,
                        ],
                    ],
                    "potentialAction" => [
                        [
                            "@type" => "OpenUri",
                            "name" => "View Event",
                            "targets" => [
                                [
                                    "os" => "default",
                                    "uri" => "https://codesign.app/events",
                                ],
                            ],
                        ],
                    ],
                ];
            case WebhookTypeEnum::N8N_WEBHOOK:
                return [
                    "brand" => "Code Sign",
                    "type" => "webhook",
                    "event_type" => $eventName->value,
                    "event_name" => ucwords(str_replace('_', ' ', $eventName->value)),
                    "timestamp" => date('c'),
                    "data" => $data,
                ];
            case WebhookTypeEnum::WHATSAPP_WEBHOOK:
                return [
                    "messaging_product" => "whatsapp",
                    "to" => $webhook->getCustomPayload()['phone'] ?? '',
                    "type" => "text",
                    "text" => [
                        "preview_url" => false,
                        "body" => "*Code Sign*\n*Event:* " . ucwords(str_replace('_', ' ', $eventName->value)) . "\n\n" . $this->formatDataAsText($data) . "\n\n_Sent by Code Sign • " . date('c') . "_"
                    ]
                ];
        }
    }

    private function formatDataAsText(array $data): string
    {
        $lines = [];
        foreach ($data as $key => $value) {
            $formattedKey = ucwords(str_replace('_', ' ', $key));
            if (is_array($value) || is_object($value)) {
                $lines[] = $formattedKey . ": " . json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $lines[] = $formattedKey . ": " . $value;
            }
        }
        return implode("\n", $lines);
    }
}