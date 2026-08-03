<?php

namespace Framework\Mailer\ValueObjects;

class Attachment
{
    /**
     * @var string $path
     */
    protected string $path;

    /**
     * @var string $name
     */
    protected string $name;

    /**
     * @var string $encoding
     */
    protected string $encoding;

    /**
     * @var string $type
     */
    protected string $type;

    /**
     * @var string $disposition
     */
    protected string $disposition;

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
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
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * @param string $encoding
     */
    public function setEncoding(string $encoding): void
    {
        $this->encoding = $encoding;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getDisposition(): string
    {
        return $this->disposition;
    }

    /**
     * @param string $disposition
     */
    public function setDisposition(string $disposition): void
    {
        $this->disposition = $disposition;
    }
}