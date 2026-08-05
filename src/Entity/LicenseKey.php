<?php

namespace App\Entity;

use App\Enum\LicenseKeyStatusEnum;
use App\Enum\LicenseKeyTypeEnum;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table("license_keys")]
class LicenseKey
{
    #[Id]
    #[Column(type: 'uuid_binary')]
    protected string $id;

    #[ManyToOne(targetEntity: Product::class, cascade: ["persist"], inversedBy: "licenseKeys")]
    #[JoinColumn(onDelete: "CASCADE")]
    protected Product $product;

    #[ManyToOne(targetEntity: Batch::class, cascade: ["persist"], inversedBy: "licenseKeys")]
    #[JoinColumn(onDelete: "CASCADE")]
    protected ?Batch $batch = null;

    #[Column(name: "license_key", type: 'string', length: 255, unique: true)]
    protected string $key;

    #[Column(name: "type", enumType: LicenseKeyTypeEnum::class)]
    protected LicenseKeyTypeEnum $type = LicenseKeyTypeEnum::perpetual;

    #[Column(name: "max_activations", type: 'integer', options: ["default" => 1])]
    protected int $max_activations = 1;

    #[Column(name: "current_activations", type: 'integer', options: ["default" => 0])]
    protected int $current_activations = 0;

    #[Column(name: "expires_at", type: 'datetime', nullable: true)]
    protected ?DateTime $expiresAt = null;

    #[Column(name: "notes", type: "text", nullable: true)]
    protected ?string $notes = null;

    #[Column(name: "status", enumType: LicenseKeyStatusEnum::class)]
    protected LicenseKeyStatusEnum $status;

    #[Column(name: "created_at", type: "datetime")]
    protected DateTime $createdAt;

    #[Column(name: "updated_at", type: "datetime")]
    protected DateTime $updatedAt;

    #[OneToMany(targetEntity: Activation::class, mappedBy: "licenseKey", orphanRemoval: true)]
    protected Collection $activations;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
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
     * @return LicenseKey
     */
    public function setId(string $id): LicenseKey
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return LicenseKey
     */
    public function setProduct(Product $product): LicenseKey
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return LicenseKey
     */
    public function setKey(string $key): LicenseKey
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return LicenseKeyTypeEnum
     */
    public function getType(): LicenseKeyTypeEnum
    {
        return $this->type;
    }

    /**
     * @param LicenseKeyTypeEnum $type
     * @return LicenseKey
     */
    public function setType(LicenseKeyTypeEnum $type): LicenseKey
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxActivations(): int
    {
        return $this->max_activations;
    }

    /**
     * @param int $max_activations
     * @return LicenseKey
     */
    public function setMaxActivations(int $max_activations): LicenseKey
    {
        $this->max_activations = $max_activations;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentActivations(): int
    {
        return $this->current_activations;
    }

    /**
     * @param int $current_activations
     * @return LicenseKey
     */
    public function setCurrentActivations(int $current_activations): LicenseKey
    {
        $this->current_activations = $current_activations;
        return $this;
    }

    /**
     * @return ?DateTime
     */
    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    /**
     * @param ?DateTime $expiresAt
     * @return LicenseKey
     */
    public function setExpiresAt(?DateTime $expiresAt): LicenseKey
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string|null $notes
     * @return LicenseKey
     */
    public function setNotes(?string $notes): LicenseKey
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return LicenseKeyStatusEnum
     */
    public function getStatus(): LicenseKeyStatusEnum
    {
        return $this->status;
    }

    /**
     * @param LicenseKeyStatusEnum $status
     * @return LicenseKey
     */
    public function setStatus(LicenseKeyStatusEnum $status): LicenseKey
    {
        $this->status = $status;
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
     * @return LicenseKey
     */
    public function setCreatedAt(DateTime $createdAt): LicenseKey
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     * @return LicenseKey
     */
    public function setUpdatedAt(DateTime $updatedAt): LicenseKey
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Batch|null
     */
    public function getBatch(): ?Batch
    {
        return $this->batch;
    }

    /**
     * @param Batch|null $batch
     * @return LicenseKey
     */
    public function setBatch(?Batch $batch): LicenseKey
    {
        $this->batch = $batch;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getActivations(): Collection
    {
        return $this->activations;
    }

    /**
     * @param Collection $activations
     * @return LicenseKey
     */
    public function setActivations(Collection $activations): LicenseKey
    {
        $this->activations = $activations;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return "lk_" . $this->id . "_" . $this->key;
    }
}