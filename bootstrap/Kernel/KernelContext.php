<?php

namespace Framework\Kernel;

/**
 * Prosty, typowo-kontekstowy magazyn danych pomiędzy krokami bootstrappingu.
 * Używany jako zamiennik luźnych tablic + callable set().
 */
class KernelContext
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}

