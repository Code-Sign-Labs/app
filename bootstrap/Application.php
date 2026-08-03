<?php

namespace Framework;

use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Framework\Configs\ConfigCore;

class Application
{
    protected string $webPath = '';
    protected string $consolePath = '';
    protected ?StandardDebugBar $debugBar = null;
    protected ?JavascriptRenderer $debugBarRenderer = null;
    protected string $healthEndpoint = '/up';
    protected array $configOverrides = [];
    public function __construct(protected string $basePath, protected int $appMode = 0, protected string $appName = '')
    {
    }

    /**
     * @param string $basePath
     * @param string $appName
     * @param int $appMode
     * @return static
     */
    public static function configure(string $basePath, string $appName = '', int $appMode = 0): static
    {
        return new static($basePath, $appMode, $appName);
    }

    /**
     * @param string $webPath
     * @param string $consolePath
     * @param string $healthEndpoint
     * @return $this
     */
    public function viaRouting(string $webPath, string $consolePath, string $healthEndpoint = '/up'): self
    {
        $this->webPath = $webPath;
        $this->consolePath = $consolePath;
        $this->healthEndpoint = $healthEndpoint;
        return $this;
    }

    /**
     * @param StandardDebugBar $debugBar
     * @param JavascriptRenderer $javascriptRenderer
     * @return $this
     */
    public function viaDebug(StandardDebugBar $debugBar, JavascriptRenderer $javascriptRenderer): self
    {
        $this->debugBar = $debugBar;
        $this->debugBarRenderer = $javascriptRenderer;
        return $this;
    }

    /**
     * @param array<string, \Framework\Configs\Config> $overrides
     * @return $this
     */
    public function viaConfigOverrides(array $overrides): self
    {
        $this->configOverrides = $overrides;
        return $this;
    }

    public function create(): void
    {
        if ($this->webPath === '') {
            $this->webPath = $this->basePath . 'configs/routes.php';
        }
        if ($this->consolePath === '') {
            $this->consolePath = $this->webPath;
        }

        $dotEnvLoader = new DotEnvLoader(
            $this->basePath . '.env'
        );
        $dotEnvLoader->load();

        $configCore = new ConfigCore(
            $this->basePath . 'configs'
            , $this->configOverrides
        );

        if($this->debugBar) {
            $this->debugBar['messages']->addMessage("Application configured", "debug");
        }
        $kernel = new Kernel(
            $this->webPath,
            $this->consolePath,
            $this->healthEndpoint,
            $this->basePath,
            $configCore,
            $this->appMode,
            $this->appName,
            $this->debugBar,
            $this->debugBarRenderer,
        );
    }
}