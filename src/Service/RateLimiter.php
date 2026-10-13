<?php
declare(strict_types=1);
namespace App\Service;

use App\Objects\RateLimitObject;
use Redis;

class RateLimiter
{
    protected Redis $redis;
    protected int $limit;
    protected int $windowSize;
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
        $this->limit = intval($_ENV["RATE_LIMITER_MAX_REQUESTS"]);
        $this->windowSize = intval($_ENV["RATE_LIMITER_TIME_WINDOW"]);
    }

    /**
     * @param string $clientKey
     * @return RateLimitObject
     */
    public function check(string $clientKey): RateLimitObject
    {
        $now = time();
        $currentWindowKey = "rate:" . $clientKey . ":" . floor($now / $this->windowSize);
        $previousWindowKey = "rate:" . $currentWindowKey . ":" . floor(($now / $this->windowSize) - 1);

        $currentRequests = (int) ($this->redis->get($currentWindowKey) ?: 0);
        $previousRequests = (int) ($this->redis->get($previousWindowKey) ?: 0);

        $timeIntoCurrentWindow = $now % $this->windowSize;
        $weight = 1 - ($timeIntoCurrentWindow / $this->windowSize);
        $estimatedRequests = floor($previousRequests * $weight + $currentRequests);

        $allowed = $estimatedRequests < $this->limit;

        if($allowed) {
            $pipe = $this->redis->multi(Redis::PIPELINE);
            $pipe->incr($currentWindowKey);
            $pipe->expire($currentWindowKey, $this->windowSize * 2);
            $pipe->exec();

            $estimatedRequests++;
        }

        $remaining = max(0, $this->limit - (int)$estimatedRequests);
        $resetIn = $this->windowSize - $timeIntoCurrentWindow;

        return (new RateLimitObject())
            ->setAllowed($allowed)
            ->setLimit($this->limit)
            ->setRemaining($remaining)
            ->setResetIn($resetIn);
    }
}