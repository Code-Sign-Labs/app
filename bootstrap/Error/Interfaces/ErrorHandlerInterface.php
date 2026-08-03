<?php

namespace Framework\Error\Interfaces;

use Framework\Error\Objects\ErrorObject;

interface ErrorHandlerInterface
{
    public static function handleError(ErrorObject $errorObject): void;
}