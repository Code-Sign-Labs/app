<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: "audit_logs")]
class AuditLog
{
    #[Id]
    #[Column(type: 'uuid_binary')]
    protected string $id;

    #[Column(name: "event", type: 'string')]
    protected string $event;

    #[ManyToOne(targetEntity: User::class, inversedBy: "audit_logs")]
    protected ?User $user;

    #[ManyToOne(targetEntity: LicenseKey::class, inversedBy: "audit_logs")]
    protected ?LicenseKey $licenseKey;

    #[ManyToOne(targetEntity: Product::class, inversedBy: "audit_logs")]
    protected ?Product $product;

    #[Column(name: "description", type: "text")]
    protected string $description;

    #[Column(name: "metadata", type: "json")]
    protected array $metadata = [];

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
     * @return AuditLog
     */
    public function setId(string $id): AuditLog
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @param string $event
     * @return AuditLog
     */
    public function setEvent(string $event): AuditLog
    {
        $this->event = $event;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return AuditLog
     */
    public function setUser(?User $user): AuditLog
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return LicenseKey|null
     */
    public function getLicenseKey(): ?LicenseKey
    {
        return $this->licenseKey;
    }

    /**
     * @param LicenseKey|null $licenseKey
     * @return AuditLog
     */
    public function setLicenseKey(?LicenseKey $licenseKey): AuditLog
    {
        $this->licenseKey = $licenseKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return AuditLog
     */
    public function setDescription(string $description): AuditLog
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     * @return AuditLog
     */
    public function setMetadata(array $metadata): AuditLog
    {
        $this->metadata = $metadata;
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
     * @return AuditLog
     */
    public function setCreatedAt(DateTime $createdAt): AuditLog
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product|null $product
     * @return AuditLog
     */
    public function setProduct(?Product $product): AuditLog
    {
        $this->product = $product;
        return $this;
    }
}