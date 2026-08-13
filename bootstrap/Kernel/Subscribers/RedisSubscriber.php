<?php
declare(strict_types=1);
namespace Framework\Kernel\Subscribers;

use Framework\Core\Container\Container;
use Framework\Core\Container\DependencyInjection;
use Framework\Core\EventManager;
use Framework\Kernel\KernelEvents;
use Framework\Kernel\KernelEventSubscriberInterface;
use Redis;

class RedisSubscriber implements KernelEventSubscriberInterface
{
    public function __construct(
        protected Container $container,
        protected DependencyInjection $dependencyInjection
    )
    {
    }

    public function subscribe(EventManager $eventManager): void
    {
        $eventManager->registerEvent(KernelEvents::CONFIGURE_REDIS, function (array $data) {
            if(!$_ENV["REDIS_HOST"] || !$_ENV["REDIS_PORT"]) return;

            $redis = new Redis();
            $redis->connect($_ENV['REDIS_HOST'], (int) $_ENV['REDIS_PORT']);
            if(isset($_ENV['REDIS_DB'])){
                $redis->select($_ENV['REDIS_DB']);
            }

            if(isset($_ENV['REDIS_PASSWORD'])){
                $redis->auth($_ENV['REDIS_PASSWORD']);
            }

            $this->container->bind(Redis::class, function () use ($redis) {
                return $redis;
            });
        });
    }
}