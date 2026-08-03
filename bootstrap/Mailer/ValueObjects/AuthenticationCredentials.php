<?php

namespace Framework\Mailer\ValueObjects;

class AuthenticationCredentials
{
    /**
     * @var string $host
     */
    protected string $host;

    /**
     * @var bool $useSMTP
     */
    protected bool $useSMTP = true;

    /**
     * @var string $email
     */
    protected string $email;

    /**
     * @var string $username
     */
    protected string $username;

    /**
     * @var string $password
     */
    protected string $password;

    /**
     * @var int $port
     */
    protected int $port;

    /**
     * @var string $encryptionType
     */
    protected string $encryptionType;

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return void
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return bool
     */
    public function isUseSMTP(): bool
    {
        return $this->useSMTP;
    }

    /**
     * @param bool $useSMTP
     * @return void
     */
    public function setUseSMTP(bool $useSMTP): void
    {
        $this->useSMTP = $useSMTP;
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
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return void
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getEncryptionType(): string
    {
        return $this->encryptionType;
    }

    /**
     * @param string $encryptionType
     * @return void
     */
    public function setEncryptionType(string $encryptionType): void
    {
        $this->encryptionType = $encryptionType;
    }
}