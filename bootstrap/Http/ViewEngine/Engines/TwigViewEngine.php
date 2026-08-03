<?php

namespace Framework\Http\ViewEngine\Engines;

use Framework\Http\ViewEngine\ViewEngineInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class TwigViewEngine implements ViewEngineInterface
{
    private Environment $twig;

    public function __construct(protected string $viewsPath, array $options = [])
    {
        $loader = new FilesystemLoader($viewsPath);
        $this->twig = new Environment($loader, $options);
    }

    /**
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }

    /**
     * @param Environment $twig
     */
    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }


    /**
     * @param string $template
     * @param array $data
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $template, array $data = []): string
    {
        if(!str_contains($template, '.twig')) {
            $template = $template . '.twig';
        }
        return $this->twig->render($template, $data);
    }
}