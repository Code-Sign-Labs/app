<?php

namespace Framework\Error;

use ErrorException;
use Framework\Addons\Prometheus\MetricRegistry;
use Framework\Addons\AddonsManager;
use Framework\Configs\ConfigCore;
use Framework\Core\Container\Container;
use Framework\Error\Interfaces\ErrorHandlerInterface;
use Framework\Error\NativeHandlers\HttpErrorHandler;
use Framework\Error\Objects\ErrorObject;
use Throwable;

class AppErrorHandler
{
    /**
     * @var array|class-string[]|mixed|null $errorHandlers
     */
    protected array $errorHandlers = [];

    /**
     * @var ?MetricRegistry $metricRegistry
     */
    protected ?MetricRegistry $metricRegistry = null;

    protected ?bool $isPrometheusEnabled = null;

    /**
     * @param ConfigCore $configCore
     * @param Container $container
     */
    public function __construct(protected ConfigCore $configCore, protected Container $container)
    {
        $this->errorHandlers = $this->configCore->getConfig('error')->get('error.errorHandlers') ?? [];
        if(empty($this->errorHandlers)) {
            $this->errorHandlers = [
                HttpErrorHandler::class
            ];
        }

        $this->isPrometheusEnabled = $this->resolvePrometheusEnabled();
    }

    private function resolvePrometheusEnabled(): bool
    {
        if ($this->isPrometheusEnabled !== null) {
            return $this->isPrometheusEnabled;
        }

        try {
            $addonsManager = $this->container->resolve(AddonsManager::class);
            $this->isPrometheusEnabled = $addonsManager->isAddonEnabled('Prometheus');
        } catch (Throwable) {
            $this->isPrometheusEnabled = false;
        }

        return $this->isPrometheusEnabled;
    }

    private function ensureMetricRegistry(): void
    {
        if ($this->metricRegistry || !$this->resolvePrometheusEnabled()) {
            return;
        }

        try {
            $this->metricRegistry = $this->container->resolve(MetricRegistry::class);
        } catch (Throwable) {
            $this->metricRegistry = null;
        }
    }

    /**
     * @param int $severity
     * @param string $message
     * @param string|null $file
     * @param int|null $line
     * @return void
     * @throws InternalErrorException
     */
    public static function staticHandleError(int $severity, string $message, ?string $file, ?int $line): void
    {
        if (!(error_reporting() & $severity)) {
            return;
        }

        throw new InternalErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * @param int $severity
     * @param string $message
     * @param string|null $file
     * @param int|null $line
     * @return void
     * @throws InternalErrorException
     */
    public function handleError(int $severity, string $message, ?string $file, ?int $line): void
    {
        $this->ensureMetricRegistry();

        if (!(error_reporting() & $severity)) {
            return;
        }
        if($this->metricRegistry) {
            $this->metricRegistry->counter('runtime_errors_total', [
                'app' => APP_NAME ?: 'app',
                'type' => (string)$severity,
            ]);
        }

        // In production, we want to include file/line information on the exception but
        // avoid dumping raw variables to stdout which makes test output noisy.
        if ($file !== null && $line !== null) {
            throw new InternalErrorException("An error throw: " . $message);
        }

        throw new InternalErrorException($message, 0, $severity);
    }

    protected function getHeaders(): array
    {
        if(function_exists('getallheaders')) return getallheaders();
        return [];
    }

    /**
     * @param Throwable $exception
     * @return ErrorObject
     */
    protected function constructErrorObject(Throwable $exception): ErrorObject
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $headers = $this->getHeaders();
        $body = file_get_contents('php://input');
        $bodyArray = json_decode($body, true) ?? [];

        return new ErrorObject(
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $requestUri,
            $method,
            $headers,
            $bodyArray
        );
    }

    /**
     * @param Throwable $exception
     * @return void
     */
    public function handleException(Throwable $exception): void
    {
        $this->ensureMetricRegistry();
        $errorObject = $this->constructErrorObject($exception);

        if($this->metricRegistry) {
            // Prevent from counting InternalErrorException to avoid infinite loops
            if(!($exception instanceof InternalErrorException)) {
                $this->metricRegistry->counter('runtime_exceptions_total', [
                    'app' => APP_NAME ?: 'app',
                    'type' => get_class($exception),
                ]);
            }
        }

        foreach ($this->errorHandlers as $handlerClass) {
            if (is_subclass_of($handlerClass, ErrorHandlerInterface::class)) {
                // Custom error handle needs to stop further propagation if it handles the error
                // If this handler does not stop propagation, the next handler will be called
                $handlerClass::handleError($errorObject);
            }
        }

        // Fallback to default handler if no custom handlers are defined
        HttpErrorHandler::handleError($errorObject);
    }
}