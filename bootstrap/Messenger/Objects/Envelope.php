<?php

namespace Framework\Messenger\Objects;

class Envelope
{
    private string $message;
    private array $stamps;

    public function __construct(string $message, array $stamps = [])
    {
        $this->message = $message;
        $this->stamps = $stamps;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getStamps(): array
    {
        return $this->stamps;
    }

    /**
     * @param $stamp
     * @return void
     */
    public function addStamp($stamp): void
    {
        $this->stamps[] = $stamp;
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        return serialize([
            'message' => $this->message,
            'stamps' => $this->stamps
        ]);
    }

    /**
     * @param string $data
     * @return self
     */
    public static function unSerialize(string $data): self
    {
        $unSerialized = unserialize($data);
        return new self($unSerialized['message'], $unSerialized['stamps']);
    }
}