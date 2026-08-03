<?php

namespace Framework\Http\Validators;

use Framework\Http\Objects\Response;

class ClickJackingValidator
{
    /**
     * @param Response $response
     * @return Response
     */
    public function handle(Response $response): Response
    {
        return $response->addHeader('X-Frame-Options', 'DENY');
    }
}