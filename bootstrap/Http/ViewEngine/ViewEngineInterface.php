<?php
declare(strict_types=1);
namespace Framework\Http\ViewEngine;

interface ViewEngineInterface
{
    public function render(string $template, array $data = []): string;
}