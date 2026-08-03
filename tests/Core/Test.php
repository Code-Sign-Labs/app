<?php

namespace Tests\Core;

use Tests\Core\TestError;
use Framework\Core\Container\Container;
use Framework\Http\Objects\Response;
use Framework\Router\Router;

abstract class Test
{
    /**
     * @var array $tests
     */
    /**
         * [
         *   "testMethod1",
         *   "testMethod2",
         * ]
     */
    protected array $tests = [];
    protected ?Container $container = null;
    protected ?Router $router = null;

    public function setUp(): void {}

    public function tearDown(): void {}

    /**
     * @param array $tests
     * @return void
     */
    public function registerTests(array $tests): void
    {
        $this->tests = $tests;
    }

    /**
     * @param Router $router
     * @return void
     */
    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        foreach ($this->tests as $test) {
            $this->setUp();
            $this->$test();
            $this->tearDown();
        }
    }

    // Assertions

    /**
     * @param $value
     * @param string $message
     * @return void
     * @throws TestError
     */
    public function assertTrue($value, string $message = ''): void
    {
        if (!is_bool($value) || $value !== true) {
            throw new TestError("Failed asserting that value is true. " . $message);
        }
    }

    /**
     * @param $value
     * @param string $message
     * @return void
     * @throws TestError
     */
    public function assertFalse($value, string $message = ''): void
    {
        if (!is_bool($value) || $value !== false) {
            throw new TestError("Failed asserting that value is false. " . $message);
        }
    }

    /**
     * @param $expected
     * @param $actual
     * @param string $message
     * @return void
     * @throws TestError
     */
    public function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected != $actual) {
            throw new TestError(sprintf("Failed asserting that %s equals %s. %s", var_export($actual, true), var_export($expected, true), $message));
        }
    }

    public function assertSame($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new TestError(sprintf("Failed asserting that %s is identical to %s. %s", var_export($actual, true), var_export($expected, true), $message));
        }
    }

    public function assertNotEquals($expected, $actual, string $message = ''): void
    {
        if ($expected == $actual) {
            throw new TestError(sprintf("Failed asserting that %s is not equal to %s. %s", var_export($actual, true), var_export($expected, true), $message));
        }
    }

    public function assertNotSame($expected, $actual, string $message = ''): void
    {
        if ($expected === $actual) {
            throw new TestError(sprintf("Failed asserting that %s is not identical to %s. %s", var_export($actual, true), var_export($expected, true), $message));
        }
    }

    public function assertNull($value, string $message = ''): void
    {
        if (!is_null($value)) {
            throw new TestError("Failed asserting that value is null. " . $message);
        }
    }

    public function assertNotNull($value, string $message = ''): void
    {
        if (is_null($value)) {
            throw new TestError("Failed asserting that value is not null. " . $message);
        }
    }

    public function assertThrows(callable $fn, string $expectedException): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            if (!is_a($e, $expectedException)) {
                throw new TestError(sprintf("Failed asserting that exception of type %s is thrown, got %s.", $expectedException, get_class($e)));
            }
            return;
        }

        throw new TestError(sprintf("Failed asserting that exception of type %s was thrown.", $expectedException));
    }

    public function assertHttpStatus(int $expected, Response $response): void
    {
        if ($response->getStatusCode() !== $expected) {
            throw new TestError(sprintf("Failed asserting response status %d equals expected %d.", $response->getStatusCode(), $expected));
        }
    }

    public function assertJsonContains(array $expected, string $json): void
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new TestError("Invalid JSON provided to assertJsonContains");
        }

        if (!$this->arrayContains($expected, $decoded)) {
            throw new TestError("Failed asserting JSON contains expected subset.");
        }
    }

    public function assertSuccessResponse(Response $response): void
    {
        if($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new TestError(sprintf("Failed asserting response is successful (2xx), got %d.", $response->getStatusCode()));
        }

        $body = $response->getBody();
        $responseBody = json_decode($body, true);
        if (!$responseBody) {
            throw new TestError("Failed asserting response body is valid JSON.");
        }

        if(!$responseBody['success']) {
            throw new TestError("Failed asserting response success field is true.");
        }
    }

    public function assertValidJson(string $json): void
    {
        $json = json_decode($json, true);
        if (!is_array($json)) {
            throw new TestError("Invalid JSON provided to assertJson.");
        }
    }

    public function assertContainsError(Response $response, string $code, ?string $errorDescription = null): void
    {
        $body = $response->getBody();
        $responseBody = json_decode($body, true);
        if (!$responseBody) {
            throw new TestError("Failed asserting response body is valid JSON.");
        }

        $responseError = $responseBody['error'];
        if(!$responseError) {
            throw new TestError("Failed asserting response error field exists.");
        }

        if($responseError['code'] !== $code) {
            throw new TestError(sprintf("Failed asserting response error code %s.", $responseError['code']));
        }

        if($errorDescription && $responseError['description'] !== $errorDescription) {
            throw new TestError(sprintf("Failed asserting response error description %s.", $responseError['description']));
        }
    }

    public function assertDataContains(Response $response, array $data): void
    {
        $body = $response->getBody();
        $responseBody = json_decode($body, true);
        if (!$responseBody) {
            throw new TestError("Failed asserting response body is valid JSON.");
        }

        $responseData = $responseBody['data'];
        if (!is_array($responseData)) {
            throw new TestError("Failed asserting response data is valid JSON.");
        }

        if(!$this->arrayContains($data, $responseData)) {
            throw new TestError("Failed asserting response data does not contain subset.");
        }
    }


    protected function decodeJsonResponse(Response $response): array
    {
        $decoded = json_decode($response->getBody(), true);
        if (!is_array($decoded)) {
            throw new TestError("Invalid JSON response body");
        }

        return $decoded;
    }

    private function arrayContains(array $subset, array $array): bool
    {
        foreach ($subset as $key => $value) {
            if (!array_key_exists($key, $array)) {
                return false;
            }

            if (is_array($value)) {
                if (!is_array($array[$key]) || !$this->arrayContains($value, $array[$key])) {
                    return false;
                }
            } else {
                if ($array[$key] != $value) {
                    return false;
                }
            }
        }

        return true;
    }
}