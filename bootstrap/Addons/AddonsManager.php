<?php

namespace Framework\Addons;

use Exception;
use Framework\Configs\Config;
use Framework\Configs\ConfigCore;
use Framework\Core\Container\Container;

class AddonsManager
{
    /**
     * @var array $addons
     */
    protected array $addons = [];

    /**
     * @var Config $config
     */
    protected Config $config;

    /**
     * @var array $enabledAddons
     */
    protected array $enabledAddons = [];

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @param Container $container
     * @throws Exception
     */
    public function __construct(protected Container $container)
    {
        // Load addon config
        /**
         * @var ConfigCore $configCore
         */
        $configCore = $this->container->resolve(ConfigCore::class);
        $this->config = $configCore->getConfig('addons');

        $this->enabledAddons = $this->config->get('addons.enabled_addons') ?? [];

        // Search for Addons/*/Manifest.php files and load them
        $addonFiles = glob(__DIR__ . '/*/Manifest.php');
        foreach ($addonFiles as $file) {
            $relativePath = str_replace(__DIR__ . '/', '', $file);
            $parts = explode('/', $relativePath);
            $addonName = $parts[0];
            if(!in_array($addonName, $this->enabledAddons)) {
                continue;
            }
            $className = "Framework\\Addons\\$addonName\\Manifest";
            if (class_exists($className)) {
                $addonInstance = new $className($this);
                if(!$addonInstance instanceof AddonInterface) {
                    continue;
                }
                $this->addons[] = $addonInstance;
            }
        }
    }

    /**
     * @param string $addonName
     * @return bool
     */
    public function isAddonEnabled(string $addonName): bool
    {
        return in_array($addonName, $this->enabledAddons);
    }

    /**
     * @return array
     */
    public function getAddons(): array
    {
        return $this->addons;
    }

    /**
     * @return void
     */
    public function initializeAddons(): void
    {
        foreach ($this->addons as $addon) {
            if (method_exists($addon, 'initialize')) {
                $addon->initialize();
                $this->container = $addon->getContainer();
            }
        }
    }
}