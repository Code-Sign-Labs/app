<?php
declare(strict_types=1);
namespace Framework\Error\Objects;

class ErrorObject
{
    protected int $severity;
    protected string $message;
    protected ?string $file;
    protected ?int $line;

    protected string $requestUri;
    protected string $method;
    protected array $headers;
    protected array $body;
    public function __construct(
        int $severity,
        string $message,
        ?string $file,
        ?int $line,
        string $requestUri,
        string $method,
        array $headers,
        array $body
    ) {
        $this->severity = $severity;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->requestUri = $requestUri;
        $this->method = $method;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getSeverity(): int
    {
        return $this->severity;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * @return int|null
     */
    public function getLine(): ?int
    {
        return $this->line;
    }

    /**
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }
}