<?php

namespace Framework\Http\ViewEngine\Engines;

use Framework\Http\ViewEngine\ViewEngineInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class BladeViewEngine implements ViewEngineInterface
{
    /**
     * @var BladeCompiler $blade
     */
    private BladeCompiler $bladeCompiler;

    private Filesystem $filesystem;

    private Factory $viewFactory;

    /**
     * @param string $viewsPath
     * @param string $cachePath
     */
    public function __construct(protected string $viewsPath, string $cachePath)
    {
        $this->filesystem = new Filesystem();
        $this->bladeCompiler = new BladeCompiler($this->filesystem, $cachePath);

        $resolver = new EngineResolver();
        $resolver->register('blade', function () {
            return new CompilerEngine(
                $this->bladeCompiler,
            );
        });

        $viewFinder = new FileViewFinder(
            $this->filesystem,
            [$this->viewsPath]
        );

        $events = new Dispatcher();

        $this->viewFactory = new Factory($resolver, $viewFinder, $events);
    }

    /**
     * @return Factory
     */
    public function getViewFactory(): Factory
    {
        return $this->viewFactory;
    }

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        return $this->viewFactory->make($template, $data)->render();
    }
}