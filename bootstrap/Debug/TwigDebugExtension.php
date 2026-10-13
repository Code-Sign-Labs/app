<?php
declare(strict_types=1);
namespace Framework\Debug;

use DebugBar\JavascriptRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigDebugExtension extends AbstractExtension
{
    public function __construct(protected JavascriptRenderer $javascriptRenderer)
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('debughead', function () {
                return $this->javascriptRenderer->renderHead();
            }, ['is_safe' => ['html']]),
            new TwigFunction('debugbar', function () {
                return $this->javascriptRenderer->render();
            }, ['is_safe' => ['html']]),
        ];
    }
}