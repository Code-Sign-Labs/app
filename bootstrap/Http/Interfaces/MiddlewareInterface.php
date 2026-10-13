<?php
declare(strict_types=1);
namespace Framework\Http\Interfaces;

use Closure;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

interface MiddlewareInterface
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response;
}