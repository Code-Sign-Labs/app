<?php
declare(strict_types=1);
namespace Framework\Http\Validators;

use Framework\Http\Objects\Response;

class CORSValidator
{
    protected array $allowedOrigins = [];
    protected array $allowedMethods = [];
    protected array $allowedHeaders = [];

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE'],
        array $allowedHeaders = ['Content-Type', 'Authorization']
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
    }

    public function handle(Response $response): ?Response
    {
        $origin = $_SERVER["HTTP_ORIGIN"] ?? '*';

        if (!in_array($origin, $this->allowedOrigins) && !in_array('*', $this->allowedOrigins)) {
            return null;
        }

        $response->addHeader('Access-Control-Allow-Origin', $origin);
        $response->addHeader('Access-Control-Allow-Methods', implode(',', $this->allowedMethods));
        $response->addHeader('Access-Control-Allow-Headers', implode(',', $this->allowedHeaders));
        $response->addHeader('Access-Control-Allow-Credentials', 'true');
        return $response;
    }
}