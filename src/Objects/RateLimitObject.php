<?php
declare(strict_types=1);
namespace App\Objects;

class RateLimitObject
{
    protected bool $allowed = false;
    protected int $limit = 0;
    protected int $remaining = 0;
    protected int $resetIn;

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * @param bool $allowed
     * @return RateLimitObject
     */
    public function setAllowed(bool $allowed): RateLimitObject
    {
        $this->allowed = $allowed;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return RateLimitObject
     */
    public function setLimit(int $limit): RateLimitObject
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getRemaining(): int
    {
        return $this->remaining;
    }

    /**
     * @param int $remaining
     * @return RateLimitObject
     */
    public function setRemaining(int $remaining): RateLimitObject
    {
        $this->remaining = $remaining;
        return $this;
    }

    /**
     * @return int
     */
    public function getResetIn(): int
    {
        return $this->resetIn;
    }

    /**
     * @param int $resetIn
     * @return RateLimitObject
     */
    public function setResetIn(int $resetIn): RateLimitObject
    {
        $this->resetIn = $resetIn;
        return $this;
    }
}