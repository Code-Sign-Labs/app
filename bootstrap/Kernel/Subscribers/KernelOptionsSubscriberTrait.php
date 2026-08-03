<?php

namespace Framework\Kernel\Subscribers;

use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Framework\Kernel\KernelOptions;

trait KernelOptionsSubscriberTrait
{
    protected function getDebugBar(?KernelOptions $options): ?StandardDebugBar
    {
        return $options?->debugBar;
    }

    protected function getDebugRenderer(?KernelOptions $options): ?JavascriptRenderer
    {
        return $options?->debugRenderer;
    }

    protected function getAppMode(?KernelOptions $options, int $fallback = 0): int
    {
        return $options?->appMode ?? $fallback;
    }
}

