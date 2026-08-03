<?php

use Framework\Configs\Config;

$config = new Config();

$config->set('namespace.controllers.namespace', '\\App\\Controller');
$config->set('namespace.controllers.path', __DIR__ . '/../src/Controller/*');

$config->set('namespace.entity.namespace', '\\App\\Entity');
$config->set('namespace.entity.path', __DIR__ . '/../src/Entity/');

$config->set('namespace.services.namespace', '\\App\\Service');
$config->set('namespace.services.path', __DIR__ . '/../src/Service/*');

$config->set('namespace.repositories.namespace', '\\App\\Repository');
$config->set('namespace.repositories.path', __DIR__ . '/../src/Repository/*');

$config->set('namespace.extensions.use', true);
$config->set('namespace.extensions.namespace', '\\App\\Extension');
$config->set('namespace.extensions.path', __DIR__ . '/../src/Extension/*');

$config->set('namespace.middlewares.namespace', '\\App\\Middleware');
$config->set('namespace.middlewares.path', __DIR__ . '/../src/Middleware/*');

$config->set('namespace.firewalls.namespace', '\\App\\Firewall');
$config->set('namespace.firewalls.path', __DIR__ . '/../src/Firewall/*');

return $config;
