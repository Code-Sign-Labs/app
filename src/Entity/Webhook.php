<?php

namespace App\Entity;

use App\Enum\WebhookTypeEnum;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: "webhooks")]
class Webhook
{
    #[Id]
    #[Column(type: 'uuid_binary')]
    protected string $id;

    #[Column(name: "name", type: "string", length: 255)]
    protected string $name;

    #[Column(name: "url", type: "text")]
    protected string $url;

    #[Column(name: "events", type: "json")]
    protected array $events = [];

    #[Column(name: "type", enumType: WebhookTypeEnum::class)]
    protected WebhookTypeEnum $type;

    #[Column(name: "headers", type: "json")]
    protected array $headers = [];

    #[Column(name: "custom_payload", type: "json")]
    protected array $customPayload = [];

    #[Column(name: "secret", type: "string", length: 255)]
    protected string $secret;

    #[Column(name: "last_delivery_at", type: "datetime", nullable: true)]
    protected ?DateTime $lastDeliveryAt;

    #[Column(name: "created_at", type: "datetime")]
    protected DateTime $createdAt;

    #[OneToMany(targetEntity: WebhookDelivery::class, mappedBy: "webhook")]
    protected Collection $webhookDeliveries;

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
     * @return Webhook
     */
    public function setId(string $id): Webhook
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Webhook
     */
    public function setName(string $name): Webhook
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Webhook
     */
    public function setUrl(string $url): Webhook
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param array $events
     * @return Webhook
     */
    public function setEvents(array $events): Webhook
    {
        $this->events = $events;
        return $this;
    }

    /**
     * @return WebhookTypeEnum
     */
    public function getType(): WebhookTypeEnum
    {
        return $this->type;
    }

    /**
     * @param WebhookTypeEnum $type
     * @return Webhook
     */
    public function setType(WebhookTypeEnum $type): Webhook
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @return Webhook
     */
    public function setHeaders(array $headers): Webhook
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return array
     */
    public function getCustomPayload(): array
    {
        return $this->customPayload;
    }

    /**
     * @param array $customPayload
     * @return Webhook
     */
    public function setCustomPayload(array $customPayload): Webhook
    {
        $this->customPayload = $customPayload;
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
     * @return Webhook
     */
    public function setCreatedAt(DateTime $createdAt): Webhook
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getLastDeliveryAt(): ?DateTime
    {
        return $this->lastDeliveryAt;
    }

    /**
     * @param DateTime|null $lastDeliveryAt
     * @return Webhook
     */
    public function setLastDeliveryAt(?DateTime $lastDeliveryAt): Webhook
    {
        $this->lastDeliveryAt = $lastDeliveryAt;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getWebhookDeliveries(): Collection
    {
        return $this->webhookDeliveries;
    }

    /**
     * @param Collection $webhookDeliveries
     * @return Webhook
     */
    public function setWebhookDeliveries(Collection $webhookDeliveries): Webhook
    {
        $this->webhookDeliveries = $webhookDeliveries;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     * @return Webhook
     */
    public function setSecret(string $secret): Webhook
    {
        $this->secret = $secret;
        return $this;
    }
}