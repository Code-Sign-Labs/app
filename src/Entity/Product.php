<?php

namespace App\Entity;

use App\Enum\ProductStatusEnum;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: 'products')]
class Product
{
    #[Id]
    #[Column(type: 'uuid_binary')]
    protected string $id;

    #[Column(name: "name", type: 'string', length: 255)]
    protected string $name;

    #[Column(name: "slug", type: 'string', length: 255)]
    protected string $slug;

    #[Column(name: "version", type: 'string', length: 50)]
    protected string $version;

    #[Column(name: "description", type: "text")]
    protected string $description;

    #[Column(name: "status", enumType: ProductStatusEnum::class)]
    protected ProductStatusEnum $status;

    #[Column(name: "created_at", type: 'datetime')]
    protected DateTime $createdAt;

    #[Column(name: "updated_at", type: 'datetime')]
    protected DateTime $updatedAt;

    #[ORM\OneToMany(targetEntity: LicenseKey::class, mappedBy: 'product', orphanRemoval: true)]
    protected Collection $licenses;

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
     * @return Product
     */
    public function setId(string $id): Product
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
     * @return Product
     */
    public function setName(string $name): Product
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return Product
     */
    public function setSlug(string $slug): Product
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return Product
     */
    public function setVersion(string $version): Product
    {
        $this->version = $version;
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
     * @return Product
     */
    public function setDescription(string $description): Product
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return ProductStatusEnum
     */
    public function getStatus(): ProductStatusEnum
    {
        return $this->status;
    }

    /**
     * @param ProductStatusEnum $status
     * @return Product
     */
    public function setStatus(ProductStatusEnum $status): Product
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
     * @return Product
     */
    public function setCreatedAt(DateTime $createdAt): Product
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
     * @return Product
     */
    public function setUpdatedAt(DateTime $updatedAt): Product
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getLicenses(): Collection
    {
        return $this->licenses;
    }

    /**
     * @param Collection $licenses
     * @return Product
     */
    public function setLicenses(Collection $licenses): Product
    {
        $this->licenses = $licenses;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return "pr_" . $this->id . "_" . $this->version;
    }
}