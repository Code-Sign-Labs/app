<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Ramsey\Uuid\Uuid;

#[Entity]
#[Table(name: 'users')]
class User
{
    #[Id]
    #[Column(type: "uuid_binary")]
    protected string $id;

    #[Column(name: "email", type: "string", unique: true)]
    protected string $email;

    #[Column(name: "name", type: "string", length: 255)]
    protected string $name;

    #[Column(name: "password", type: "string", length: 255)]
    protected string $password;

    #[Column(name: "created_at", type: "datetime")]
    protected \DateTime $createdAt;

    #[ORM\OneToMany(targetEntity: AuditLog::class, mappedBy: "user", orphanRemoval: true)]
    protected Collection $auditLogs;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->createdAt = new \DateTime();
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
     * @return User
     */
    public function setId(string $id): User
    {
        $this->id = $id;
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
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;
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
     * @return User
     */
    public function setName(string $name): User
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return User
     */
    public function setCreatedAt(\DateTime $createdAt): User
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getAuditLogs(): Collection
    {
        return $this->auditLogs;
    }

    /**
     * @param Collection $auditLogs
     * @return User
     */
    public function setAuditLogs(Collection $auditLogs): User
    {
        $this->auditLogs = $auditLogs;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return "usr_" . $this->id . "_" . $this->name;
    }
}