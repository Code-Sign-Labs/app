<?php
declare(strict_types=1);
namespace Framework\Mailer\ValueObjects;

class Reply
{
    /**
     * @var string $email
     */
    protected string $email;

    /**
     * @var string $name
     */
    protected string $name;

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return ?string
     */
    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}