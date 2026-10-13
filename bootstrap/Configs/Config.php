<?php
declare(strict_types=1);
namespace Framework\Configs;

class Config
{
    /**
     * @var array $values
     */
    protected array $values = [];

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }
}