<?php

namespace Framework\Error\NativeHandlers;

use Framework\Error\Interfaces\ErrorHandlerInterface;
use Framework\Error\Objects\ErrorObject;
use Framework\AppEnum;

class HttpErrorHandler implements ErrorHandlerInterface
{
    /**
     * @param ErrorObject $errorObject
     * @return void
     */
    public static function handleError(ErrorObject $errorObject): void
    {
        @file_put_contents(__DIR__ . '/../../../storage/logs/error.log', "[{$errorObject->getRequestUri()}] {" . $errorObject->getFile() . "} {" . $errorObject->getLine() . "} " .  $errorObject->getmessage() . "\n", FILE_APPEND);

        // In testing mode throw an exception so test runner can catch and present
        // a clean, concise message instead of raw dumps.
        if (defined('APP_MODE') && APP_MODE === AppEnum::APP_MODE_TESTING) {
            throw new \RuntimeException($errorObject->getmessage());
        }

        // Production: minimal output and HTTP 500
        http_response_code(500);
        echo 'An internal server error occurred.';
        exit(1);
    }
}