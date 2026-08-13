<?php
declare(strict_types=1);
namespace App\Entity;

use App\Enum\EventNameEnum;
use App\Enum\WebhookDeliveryStatusEnum;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: "webhook_deliveries")]
class WebhookDelivery
{
    #[Id]
    #[Column(type: "uuid_binary")]
    protected string $id;

    #[ManyToOne(targetEntity: Webhook::class, inversedBy: "webhookDeliveries")]
    protected Webhook $webhook;

    #[Column(name: "status", enumType: WebhookDeliveryStatusEnum::class)]
    protected WebhookDeliveryStatusEnum $status;

    #[Column(name: "event_name", enumType: EventNameEnum::class)]
    protected EventNameEnum $eventName;

    #[Column(name: "response_headers", type: "json", nullable: true)]
    protected ?array $responseHeaders = null;

    #[Column(name: "response_body", type: "text", nullable: true)]
    protected ?string $responseBody = null;

    #[Column(name: "request_headers", type: "json")]
    protected array $requestHeaders = [];

    #[Column(name: "request_body", type: "json")]
    protected array $requestBody = [];

    #[Column(name: "signature", type: "text")]
    protected string $signature;

    #[Column(name: "delivery_at", type: "datetime", nullable: true)]
    protected ?DateTime $deliveryAt = null;

    #[Column(name: "created_at", type: "datetime")]
    protected DateTime $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new DateTime();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return WebhookDelivery
     */
    public function setId(string $id): WebhookDelivery
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Webhook
     */
    public function getWebhook(): Webhook
    {
        return $this->webhook;
    }

    /**
     * @param Webhook $webhook
     * @return WebhookDelivery
     */
    public function setWebhook(Webhook $webhook): WebhookDelivery
    {
        $this->webhook = $webhook;
        return $this;
    }

    /**
     * @return WebhookDeliveryStatusEnum
     */
    public function getStatus(): WebhookDeliveryStatusEnum
    {
        return $this->status;
    }

    /**
     * @param WebhookDeliveryStatusEnum $status
     * @return WebhookDelivery
     */
    public function setStatus(WebhookDeliveryStatusEnum $status): WebhookDelivery
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return ?array
     */
    public function getResponseHeaders(): ?array
    {
        return $this->responseHeaders;
    }

    /**
     * @param ?array $responseHeaders
     * @return WebhookDelivery
     */
    public function setResponseHeaders(?array $responseHeaders): WebhookDelivery
    {
        $this->responseHeaders = $responseHeaders;
        return $this;
    }

    /**
     * @return ?string
     */
    public function getResponseBody(): ?string
    {
        return base64_decode($this->responseBody);
    }

    /**
     * @param ?string $responseBody
     * @return WebhookDelivery
     */
    public function setResponseBody(?string $responseBody): WebhookDelivery
    {
        $this->responseBody = base64_encode($responseBody);
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return WebhookDelivery
     */
    public function setCreatedAt(DateTime $createdAt): WebhookDelivery
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    /**
     * @param array $requestHeaders
     * @return WebhookDelivery
     */
    public function setRequestHeaders(array $requestHeaders): WebhookDelivery
    {
        $this->requestHeaders = $requestHeaders;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestBody(): array
    {
        return $this->requestBody;
    }

    /**
     * @param array $requestBody
     * @return WebhookDelivery
     */
    public function setRequestBody(array $requestBody): WebhookDelivery
    {
        $this->requestBody = $requestBody;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDeliveryAt(): ?DateTime
    {
        return $this->deliveryAt;
    }

    /**
     * @param DateTime|null $deliveryAt
     * @return WebhookDelivery
     */
    public function setDeliveryAt(?DateTime $deliveryAt): WebhookDelivery
    {
        $this->deliveryAt = $deliveryAt;
        return $this;
    }

    /**
     * @return EventNameEnum
     */
    public function getEventName(): EventNameEnum
    {
        return $this->eventName;
    }

    /**
     * @param EventNameEnum $eventName
     * @return WebhookDelivery
     */
    public function setEventName(EventNameEnum $eventName): WebhookDelivery
    {
        $this->eventName = $eventName;
        return $this;
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     * @return WebhookDelivery
     */
    public function setSignature(string $signature): WebhookDelivery
    {
        $this->signature = $signature;
        return $this;
    }
}