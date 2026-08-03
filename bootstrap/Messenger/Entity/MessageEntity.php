<?php

namespace Framework\Messenger\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "messenger_messages")]
class MessageEntity
{
    #[Column(name: "id", type: "integer")]
    #[Id]
    #[GeneratedValue]
    protected int $id;

    #[Column(name: "content", type: "text", length: 65535)]
    protected string $content;

    #[Column(name: "created_at", type: "datetime")]
    protected DateTime $createdAt;

    #[Column(name: "available_at", type: 'datetime')]
    protected DateTime $availableAt;

    #[Column(name: "delivered_at", type: 'datetime', nullable: true)]
    protected ?DateTime $deliveredAt = null;

    #[Column(name: "status")]
    protected int $status;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return MessageEntity
     */
    public function setId(int $id): MessageEntity
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return MessageEntity
     */
    public function setContent(string $content): MessageEntity
    {
        $this->content = $content;
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
     * @return MessageEntity
     */
    public function setCreatedAt(DateTime $createdAt): MessageEntity
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getAvailableAt(): DateTime
    {
        return $this->availableAt;
    }

    /**
     * @param DateTime $availableAt
     * @return MessageEntity
     */
    public function setAvailableAt(DateTime $availableAt): MessageEntity
    {
        $this->availableAt = $availableAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDeliveredAt(): ?DateTime
    {
        return $this->deliveredAt;
    }

    /**
     * @param DateTime|null $deliveredAt
     * @return MessageEntity
     */
    public function setDeliveredAt(?DateTime $deliveredAt): MessageEntity
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return MessageEntity
     */
    public function setStatus(int $status): MessageEntity
    {
        $this->status = $status;
        return $this;
    }
}