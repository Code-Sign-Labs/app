<?php
declare(strict_types=1);
namespace App\Messenger\Message;

use DateTime;
use Framework\Messenger\Interfaces\MessageHandlerInterface;
use Framework\Messenger\Interfaces\MessageInterface;
use Framework\Messenger\Objects\Envelope;

class EmailMessage implements MessageInterface
{
    /**
     * @var string
     */
    protected string $content;

    /**
     * @var DateTime
     */
    protected DateTime $available;

    /**
     * @var string
     */
    protected string $message;

    /**
     * @var string
     */
    protected string $email;

    /**
     * @var MessageHandlerInterface
     */
    protected MessageHandlerInterface $handler;

    /**
     * @var mixed[]
     */
    protected array $additionalData;

    /**
     * @return string
     */
    public function getContent(): string
    {
        $envelope = new Envelope($this->getMessage(), [
            'email'          => $this->getEmail(),
            'additionalData' => $this->getAdditionalData(),
            'handler'        => $this->getHandler(),
        ]);

        return $envelope->serialize();
    }

    /**
     * @param string $content
     *
     * @return EmailMessage
     */
    public function setContent(string $content): EmailMessage
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getAvailable(): DateTime
    {
        return $this->available;
    }

    /**
     * @param DateTime $available
     *
     * @return EmailMessage
     */
    public function setAvailable(DateTime $available): EmailMessage
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return EmailMessage
     */
    public function setMessage(string $message): EmailMessage
    {
        $this->message = $message;

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
     *
     * @return EmailMessage
     */
    public function setEmail(string $email): EmailMessage
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return MessageHandlerInterface
     */
    public function getHandler(): MessageHandlerInterface
    {
        return $this->handler;
    }

    /**
     * @param MessageHandlerInterface $handler
     *
     * @return EmailMessage
     */
    public function setHandler(MessageHandlerInterface $handler): EmailMessage
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    /**
     * @param mixed[] $additionalData
     *
     * @return EmailMessage
     */
    public function setAdditionalData(array $additionalData): EmailMessage
    {
        $this->additionalData = $additionalData;

        return $this;
    }
}
