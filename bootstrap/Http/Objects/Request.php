<?php
declare(strict_types=1);
namespace Framework\Http\Objects;

use Framework\Http\Managers\Session;

class Request
{
    protected array $headers = [];
    protected array $query = [];
    protected array $body = [];
    protected array $cookies = [];
    protected array $files = [];
    protected Session $sessionInstance;
    protected array $params = [];
    protected string $uri;
    protected array $attributes = [];

    public function __construct(?array $headers = null, ?array $query = null, ?array $body = null, ?array $cookies = null, ?array $files = null, ?array $params = null, ?string $uri = null)
    {
        $this->headers = $headers ?? $this->parseHeaders();
        $this->query = $query ?? $_GET;
        $this->body = $body ?? $this->parseBodyRequest();
        $this->files = $files ?? $_FILES;
        $this->cookies = $cookies ?? $_COOKIE;
        $this->params = $params ?? [];
        $this->uri = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');
    }

    /**
     * @return array
     */
    protected function parseHeaders(): array
    {
        if(function_exists('getallheaders')) return getallheaders();
        return [];
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return Session
     */
    public function session(): Session
    {
        if (!isset($this->sessionInstance)) {
            $this->sessionInstance = new Session();
        }

        return $this->sessionInstance;
    }

    /**
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
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
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getFile(string $key, mixed $default = null): mixed
    {
        return $this->files[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getCookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getBody(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function getAllBody(): array
    {
        return $this->body;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return string|null
     */
    public function getHeader(string $key, mixed $default = null): ?string
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     * @return Request
     */
    public function setParams(array $params): Request
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return array
     */
    protected function parseBodyRequest(): array
    {
        $rawBody = file_get_contents('php://input');
        $contentType = $this->getHeader('Content-Type') ?? "application/octet-stream";

        if(stripos($contentType, 'application/json') !== false) {
            $data = json_decode($rawBody, true);

            return json_last_error() === JSON_ERROR_NONE ? $data : [];
        }
        elseif (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($rawBody, $data);
            return $data;
        }
        elseif (stripos($contentType, 'multipart/form-data') !== false) {
            return $_POST;
        }
        else {
            return [];
        }
    }

    /**
     * @return mixed[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param mixed[] $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @return string|null
     */
    public function getUserIp(): ?string
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_FORWARDED'] ?? $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ?? $_SERVER['HTTP_FORWARDED_FOR'] ?? $_SERVER['HTTP_FORWARDED'] ?? $_SERVER['REMOTE_ADDR'];
    }
}