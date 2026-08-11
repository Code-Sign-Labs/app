<?php
declare(strict_types=1);
use Framework\Configs\Config;

$config = new Config();

$config->set('orm.use', true);
$config->set('orm.connection', [
    'driver'   => 'pdo_mysql',
    'user'     => $_ENV['DB_USER']     ?? '',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'dbname'   => $_ENV['DB_NAME']     ?? '',
    'host'     => $_ENV['DB_HOST']     ?? '',
    'charset'  => $_ENV['DB_CHARSET']  ?? 'utf8mb4',
]);

return $config;
