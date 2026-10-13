<?php
declare(strict_types=1);
namespace Framework\Http\Interfaces;

use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

interface FirewallInterface
{
    public function handle(Request $request): bool;
    public function onFailure(): Response;
}