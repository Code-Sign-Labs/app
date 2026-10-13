<?php
declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: 'customers')]
class Customer
{
    #[Id]
    #[Column(type: 'uuid_binary')]
    protected string $id;

    #[Column(name: "label", type: 'string', length: 255)]
    protected string $label;

    #[Column(name: "email", type: 'string', length: 255)]
    protected string $email;

    #[Column(name: "address", type: 'string', length: 255)]
    protected string $address;

    #[Column(name: "country", type: 'string', length: 255)]
    protected string $country;

    #[Column(name: "postal_code", type: 'string', length: 255)]
    protected string $postalCode;

    #[Column(name: "city", type: 'string', length: 255)]
    protected string $city;

    #[Column(name: "currency", type: 'string', length: 255)]
    protected string $currency;

    #[Column(name: "notes", type: "text", nullable: true)]
    protected ?string $notes = null;

    #[Column(name: "vat_number", type: 'string', length: 255, nullable: true)]
    protected ?string $vatNumber = null;

    #[Column(name: "stripe_customer_id", type: 'string', length: 255, nullable: true)]
    protected ?string $stripeCustomerId = null;

    #[Column(name: "metadata", type: 'json')]
    protected array $metadata = [];

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
     * @return Customer
     */
    public function setId(string $id): Customer
    {
        $this->id = $id;
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
     * @return Customer
     */
    public function setLabel(string $label): Customer
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Customer
     */
    public function setEmail(string $email): Customer
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return Customer
     */
    public function setAddress(string $address): Customer
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return Customer
     */
    public function setCountry(string $country): Customer
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     * @return Customer
     */
    public function setPostalCode(string $postalCode): Customer
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return Customer
     */
    public function setCity(string $city): Customer
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return Customer
     */
    public function setCurrency(string $currency): Customer
    {
        $this->currency = $currency;
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
     * @return Customer
     */
    public function setMetadata(array $metadata): Customer
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
     * @return Customer
     */
    public function setCreatedAt(DateTime $createdAt): Customer
    {
        $this->createdAt = $createdAt;
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
     * @return Customer
     */
    public function setNotes(?string $notes): Customer
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    /**
     * @param string|null $vatNumber
     * @return Customer
     */
    public function setVatNumber(?string $vatNumber): Customer
    {
        $this->vatNumber = $vatNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    /**
     * @param string|null $stripeCustomerId
     * @return Customer
     */
    public function setStripeCustomerId(?string $stripeCustomerId): Customer
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }
}