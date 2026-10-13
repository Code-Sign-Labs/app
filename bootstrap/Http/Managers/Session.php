<?php
declare(strict_types=1);
namespace Framework\Http\Managers;

class Session
{
    public function __construct()
    {
        // Initialize session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * @return void
     */
    public function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $_SESSION;
    }
}