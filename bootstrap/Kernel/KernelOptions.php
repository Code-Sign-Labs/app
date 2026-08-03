<?php

namespace Framework\Kernel;

use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;

class KernelOptions
{
    public function __construct(
        public readonly int $appMode,
        public readonly string $appName = '',
        public readonly string $basePath = '',
        public readonly ?StandardDebugBar $debugBar = null,
        public readonly ?JavascriptRenderer $debugRenderer = null,
    ) {
    }
}
