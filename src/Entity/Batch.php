<?php
declare(strict_types=1);
namespace App\Entity;

use App\Enum\LicenseKeyTypeEnum;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: "batches")]
class Batch
{
    #[Id]
    #[Column(type: 'uuid_binary')]
    protected string $id;

    #[ManyToOne(targetEntity: Product::class, inversedBy: "batches")]
    protected Product $product;

    #[Column(name: "label", type: 'string', length: 255)]
    protected string $label;

    #[Column(name: "quantity", type: 'integer')]
    protected int $quantity;

    #[Column(name: "type", enumType: LicenseKeyTypeEnum::class)]
    protected LicenseKeyTypeEnum $type = LicenseKeyTypeEnum::perpetual;

    #[Column(name: "max_activations", type: 'integer', options: ['default' => 1])]
    protected int $maxActivations = 1;

    #[Column(name: "expires_at", type: 'datetime', nullable: true)]
    protected ?DateTime $expiresAt;

    #[Column(name: "exported_at", type: 'datetime', nullable: true)]
    protected ?DateTime $exportedAt = null;

    #[Column(name: "created_at", type: 'datetime')]
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
     * @return Batch
     */
    public function setId(string $id): Batch
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
     * @return Batch
     */
    public function setProduct(Product $product): Batch
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return Batch
     */
    public function setLabel(string $label): Batch
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return Batch
     */
    public function setQuantity(int $quantity): Batch
    {
        $this->quantity = $quantity;
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
     * @return Batch
     */
    public function setType(LicenseKeyTypeEnum $type): Batch
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxActivations(): int
    {
        return $this->maxActivations;
    }

    /**
     * @param int $maxActivations
     * @return Batch
     */
    public function setMaxActivations(int $maxActivations): Batch
    {
        $this->maxActivations = $maxActivations;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    /**
     * @param DateTime|null $expiresAt
     * @return Batch
     */
    public function setExpiresAt(?DateTime $expiresAt): Batch
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getExportedAt(): ?DateTime
    {
        return $this->exportedAt;
    }

    /**
     * @param DateTime|null $exportedAt
     * @return Batch
     */
    public function setExportedAt(?DateTime $exportedAt): Batch
    {
        $this->exportedAt = $exportedAt;
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
     * @return Batch
     */
    public function setCreatedAt(DateTime $createdAt): Batch
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}