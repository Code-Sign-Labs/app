<?php

namespace Framework\Addons\Prometheus\Handlers;

use Exception;
use Framework\Addons\AddonsManager;
use Framework\Addons\Prometheus\Manifest;
use Framework\Addons\Prometheus\MetricRegistry;
use Framework\Core\Container\Container;
use Framework\Core\EventHandlerInterface;

class PrometheusRequestCompletedHandler implements EventHandlerInterface
{
    protected bool $isPrometheusEnabled = false;
    protected AddonsManager $addonsManager;
    protected MetricRegistry $metricRegistry;
    protected Manifest $prometheusManifest;

    /**
     * @param Container $container
     * @throws Exception
     */
    public function __construct(protected Container $container)
    {
        $this->addonsManager = $this->container->resolve(AddonsManager::class);
        $this->isPrometheusEnabled = $this->addonsManager->isAddonEnabled("Prometheus");
        if(!$this->isPrometheusEnabled) return;
        $this->prometheusManifest = $this->container->resolve(Manifest::class);
        $this->metricRegistry = $this->container->resolve(MetricRegistry::class);
    }

    /**
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function handle(array $data): void
    {
        if(!$this->isPrometheusEnabled) return;
        if($this->prometheusManifest->isFeatureEnabled('count_total_requests')) {
            $this->metricRegistry->counter("http_request_total", [
                'app' => APP_NAME ?: 'app',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'path' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'status' => http_response_code() ? (string)http_response_code() : '200',
            ]);
        }

        if($this->prometheusManifest->isFeatureEnabled('track_request_duration')) {
            $this->metricRegistry->histogram(
                "http_request_duration_seconds",
                microtime(true) - ($_SERVER["REQUEST_TIME_FLOAT"] ?? microtime(true)),
                [
                    'app' => APP_NAME ?: 'app',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                    'path' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                ]
            );
        }

        if($this->prometheusManifest->isFeatureEnabled('monitor_memory_usage')) {
            $this->metricRegistry->gauge("runtime_memory_usage_bytes", (float)memory_get_usage(), [
                'app' => APP_NAME ?: 'app',
            ]);
            $this->metricRegistry->gauge("runtime_memory_limit_bytes", (float)ini_get('memory_limit') * 1024 * 1024, [
                'app' => APP_NAME ?: 'app',
            ]);
        }
    }
}