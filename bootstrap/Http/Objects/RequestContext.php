<?php

namespace Framework\Http\Objects;

final class RequestContext
{
    public function __construct(
        public readonly string $requestId,
        public readonly float $startedAtFloat,
        public readonly string $method,
        public readonly string $uri,
        public readonly ?string $ip = null,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $startedAtFloat = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        $requestId = $_SERVER['HTTP_X_REQUEST_ID']
            ?? $_SERVER['HTTP_X_CORRELATION_ID']
            ?? bin2hex(random_bytes(16));

        return new self($requestId, $startedAtFloat, $method, $uri, $ip);
    }

    public function durationSeconds(?float $now = null): float
    {
        $now = $now ?? microtime(true);
        return $now - $this->startedAtFloat;
    }
}
