<?php

namespace Framework\Http\ViewEngine;

use Framework\Http\ViewEngine\Engines\BladeViewEngine;
use Framework\Http\ViewEngine\Engines\TwigViewEngine;
use InvalidArgumentException;

class ViewFactory
{
    /**
     * @param string $engine
     * @param array $config
     * @return ViewEngineInterface
     */
    public static function create(string $engine, array $config): ViewEngineInterface
    {
        return match(strtolower($engine)) {
            'blade' => new BladeViewEngine($config['viewsPath'], $config['cachePath'] ?? ''),
            'twig' => new TwigViewEngine($config['viewsPath'], $config['options'] ?? []),
            default => throw new InvalidArgumentException("View engine [$engine] not supported."),
        };
    }
}