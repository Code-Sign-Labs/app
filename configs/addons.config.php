<?php

$config = new \Framework\Configs\Config();

$config->set('addons.enabled_addons', [

]);

$config->set('addons.configs', [
    'Prometheus' => [
        // HTTP route to expose metrics
        'route' => '/metrics',
        'method' => "GET",
        'firewall' => null,

        // Features to enable
        'features' => [
            'count_total_requests' => true,
            'track_request_duration' => true,
            'monitor_memory_usage' => true
        ]
    ]
]);

return $config;
