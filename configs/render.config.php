<?php
declare(strict_types=1);
use Framework\Configs\Config;

$config = new Config();

$config->set('view.use', true);
$config->set('view.engine', 'twig');
$config->set('view.path', __DIR__ . '/../storage/templates/');
$config->set('view.blade.options', [
    'cachePath' => __DIR__ . '/../storage/cache/views',
]);
$config->set('view.twig.options', [
    'debug'       => false,
    'auto_reload' => true,
]);

return $config;
