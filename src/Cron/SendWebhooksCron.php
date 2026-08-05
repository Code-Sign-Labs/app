<?php

namespace App\Cron;

use App\Entity\WebhookDelivery;
use App\Enum\WebhookDeliveryStatusEnum;
use DateTime;
use Doctrine\ORM\EntityManager;
use Throwable;

class SendWebhooksCron
{
    public function __construct(protected EntityManager $entityManager)
    {
    }

    public function run(): void
    {
        $webhooksDeliveries = $this->entityManager->getRepository(WebhookDelivery::class)->findBy([
            'status' => WebhookDeliveryStatusEnum::await_delivery
        ]);

        echo "Detected " . count($webhooksDeliveries) . " webhook delivery" . PHP_EOL;

        try {
            foreach ($webhooksDeliveries as $webhookDelivery) {
                echo "Sending webhook " . $webhookDelivery->getId() . PHP_EOL;

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $webhookDelivery->getWebhook()->getUrl());
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                // set headers
                $headers = $webhookDelivery->getRequestHeaders();
                $formattedHeaders = [
                    "Content-Type: application/json",
                    "Content-Length: " . strlen(json_encode($webhookDelivery->getRequestBody())),
                    "X-CODE-SIGN-WEBHOOK-SIGNATURE: " . $webhookDelivery->getSignature()
                ];
                foreach ($headers as $key => $value) {
                    $formattedHeaders[] = "$key: $value";
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);

                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookDelivery->getRequestBody()));
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);

                curl_close($ch);
                if($httpCode >= 200 && $httpCode < 300) {
                    $webhookDelivery->setStatus(WebhookDeliveryStatusEnum::success);
                    echo "Webhook delivery status " . $webhookDelivery->getId() . PHP_EOL;
                } else {
                    $webhookDelivery->setStatus(WebhookDeliveryStatusEnum::error);
                    echo "Webhook delivery failed " . $webhookDelivery->getId() . PHP_EOL;
                }

                $webhookDelivery->setResponseBody($response);
                $webhookDelivery->setResponseHeaders($headers ?: []);
                $webhookDelivery->setDeliveryAt(new DateTime());
                $webhook = $webhookDelivery->getWebhook();
                $webhook->setLastDeliveryAt(new DateTime());

                $this->entityManager->persist($webhook);
                $this->entityManager->persist($webhookDelivery);
            }

            $this->entityManager->flush();
            echo "Webhook delivery finished" . PHP_EOL;
        } catch(Throwable $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }
}