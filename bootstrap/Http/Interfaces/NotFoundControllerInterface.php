<?php

namespace Framework\Http\Interfaces;

use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

interface NotFoundControllerInterface
{
    public function handle(Request $request): Response;
}