<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: "config")]
class Config
{
    #[Id]
    #[GeneratedValue(strategy: "AUTO")]
    #[Column(name: "id", type: 'integer')]
    protected int $id;

    #[Column(name: "instance_name", type: 'string', length: 255)]
    protected string $instanceName;

    #[Column(name: "base_url", type: 'string', length: 255)]
    protected string $baseUrl;

    #[Column(name: "key_prefix", type: 'string', length: 255)]
    protected string $keyPrefix;

    #[Column(name: "key_pattern", type: 'string', length: 255)]
    protected string $keyPattern;

    #[Column(name: "min_password_length", type: 'integer')]
    protected int $minPasswordLength;

    #[Column(name: "password_requires_uppercase", type: 'boolean')]
    protected bool $passwordRequiresUppercase;

    #[Column(name: "password_requires_numbers", type: 'boolean')]
    protected bool $passwordRequiresNumbers;

    #[Column(name: "password_requires_special", type: 'boolean')]
    protected bool $passwordRequiresSpecial;

    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Config
     */
    public function setId(int $id): Config
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getInstanceName(): string
    {
        return $this->instanceName;
    }

    /**
     * @param string $instanceName
     * @return Config
     */
    public function setInstanceName(string $instanceName): Config
    {
        $this->instanceName = $instanceName;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     * @return Config
     */
    public function setBaseUrl(string $baseUrl): Config
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * @param string $keyPrefix
     * @return Config
     */
    public function setKeyPrefix(string $keyPrefix): Config
    {
        $this->keyPrefix = $keyPrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyPattern(): string
    {
        return $this->keyPattern;
    }

    /**
     * @param string $keyPattern
     * @return Config
     */
    public function setKeyPattern(string $keyPattern): Config
    {
        $this->keyPattern = $keyPattern;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinPasswordLength(): int
    {
        return $this->minPasswordLength;
    }

    /**
     * @param int $minPasswordLength
     * @return Config
     */
    public function setMinPasswordLength(int $minPasswordLength): Config
    {
        $this->minPasswordLength = $minPasswordLength;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPasswordRequiresUppercase(): bool
    {
        return $this->passwordRequiresUppercase;
    }

    /**
     * @param bool $passwordRequiresUppercase
     * @return Config
     */
    public function setPasswordRequiresUppercase(bool $passwordRequiresUppercase): Config
    {
        $this->passwordRequiresUppercase = $passwordRequiresUppercase;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPasswordRequiresNumbers(): bool
    {
        return $this->passwordRequiresNumbers;
    }

    /**
     * @param bool $passwordRequiresNumbers
     * @return Config
     */
    public function setPasswordRequiresNumbers(bool $passwordRequiresNumbers): Config
    {
        $this->passwordRequiresNumbers = $passwordRequiresNumbers;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPasswordRequiresSpecial(): bool
    {
        return $this->passwordRequiresSpecial;
    }

    /**
     * @param bool $passwordRequiresSpecial
     * @return Config
     */
    public function setPasswordRequiresSpecial(bool $passwordRequiresSpecial): Config
    {
        $this->passwordRequiresSpecial = $passwordRequiresSpecial;
        return $this;
    }
}