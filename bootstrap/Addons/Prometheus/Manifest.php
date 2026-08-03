<?php

namespace Framework\Addons\Prometheus;

use Exception;
use Framework\Addons\AddonInterface;
use Framework\Addons\AddonsManager;
use Framework\Configs\ConfigCore;
use Framework\Core\Container\Container;

class Manifest implements AddonInterface
{
    protected AddonsManager $addonsManager;
    protected Container $container;
    protected ConfigCore $config;
    public function __construct(AddonsManager $addonsManager)
    {
        $this->container = $addonsManager->getContainer();
        $this->addonsManager = $addonsManager;
        $this->config = $this->container->resolve(ConfigCore::class);
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @return void
     */
    public function initialize(): void
    {
        $this->container->bind(RedisDriver::class, function() {
            return new RedisDriver();
        });
        $this->container->bind(MetricRegistry::class, function() {
            return new MetricRegistry($this->container->resolve(RedisDriver::class));
        });
    }

    /**
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public function isFeatureEnabled(string $name): bool
    {
        return $this->config->getConfig('addons')->get('addons.configs')['Prometheus']['features'][$name] === true;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getConfig(string $name): mixed
    {
        return $this->config->getConfig('addons')->get('addons.configs')['Prometheus'][$name] ?? null;
    }
}