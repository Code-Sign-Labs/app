<?php

namespace Framework\Addons\Prometheus;

use Predis\Client;

class RedisDriver
{
    protected Client $client;
    public function __construct()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => $_ENV["REDIS_HOST"],
            'port'   => $_ENV["REDIS_PORT"],
            'password' => $_ENV["REDIS_PASSWORD"] ?? null,
            'database' => $_ENV["REDIS_DB"] ?? 0,
        ]);
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}