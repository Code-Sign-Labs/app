<?php
declare(strict_types=1);
namespace Framework\Configs;

class ConfigCore
{
    protected array $configs = [];

    public function __construct(protected string $configsPath, protected array $overrides = [])
    {
        foreach( glob($this->configsPath . '/*.config.php') as $file) {
            $configName = basename($file, '.config.php');
            $config = require $file;
            if($config instanceof Config) {
                $this->configs[$configName] = require $file;
            }
        }

        foreach ($this->overrides as $name => $config) {
            if ($config instanceof Config) {
                $this->configs[$name] = $config;
            }
        }
    }

    /**
     * @param string $name
     * @return Config|null
     */
    public function getConfig(string $name): ?Config
    {
        return $this->configs[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }
}