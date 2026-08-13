<?php
declare(strict_types=1);
namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: "activations")]
class Activation
{
    #[Id]
    #[Column(type: 'uuid_binary')]
    protected string $id;

    #[ManyToOne(targetEntity: LicenseKey::class, inversedBy: "activations")]
    protected LicenseKey $licenseKey;

    #[Column(name: "device_identifier", type: "string", length: 500)]
    protected string $deviceIdentifier;

    #[Column(name: "device_name", type: "string", length: 255, nullable: true)]
    protected ?string $deviceName;

    #[Column(name: "ip_address", type: "string", length: 45)]
    protected string $ipAddress;

    #[Column(name: "metadata", type: "json", nullable: true)]
    protected ?array $metadata = [];

    #[Column(name: "active", type: "boolean", nullable: true)]
    protected bool $active;

    #[Column(name: "deactivated_at", type: "datetime", nullable: true)]
    protected ?DateTime $deactivatedAt = null;

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
     * @return Activation
     */
    public function setId(string $id): Activation
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return LicenseKey
     */
    public function getLicenseKey(): LicenseKey
    {
        return $this->licenseKey;
    }

    /**
     * @param LicenseKey $licenseKey
     * @return Activation
     */
    public function setLicenseKey(LicenseKey $licenseKey): Activation
    {
        $this->licenseKey = $licenseKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeviceIdentifier(): string
    {
        return $this->deviceIdentifier;
    }

    /**
     * @param string $deviceIdentifier
     * @return Activation
     */
    public function setDeviceIdentifier(string $deviceIdentifier): Activation
    {
        $this->deviceIdentifier = $deviceIdentifier;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    /**
     * @param string|null $deviceName
     * @return Activation
     */
    public function setDeviceName(?string $deviceName): Activation
    {
        $this->deviceName = $deviceName;
        return $this;
    }

    /**
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     * @return Activation
     */
    public function setIpAddress(string $ipAddress): Activation
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array|null $metadata
     * @return Activation
     */
    public function setMetadata(?array $metadata): Activation
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return Activation
     */
    public function setActive(bool $active): Activation
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDeactivatedAt(): ?DateTime
    {
        return $this->deactivatedAt;
    }

    /**
     * @param DateTime|null $deactivatedAt
     * @return Activation
     */
    public function setDeactivatedAt(?DateTime $deactivatedAt): Activation
    {
        $this->deactivatedAt = $deactivatedAt;
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
     * @return Activation
     */
    public function setCreatedAt(DateTime $createdAt): Activation
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}