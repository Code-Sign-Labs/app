<?php

use Framework\AppEnum;
use Framework\Application;
use Framework\Configs\Config;
use Framework\DotEnvLoader;

const BASE_DIR = __DIR__ . '/../../';

require BASE_DIR . 'vendor/autoload.php';

const APP_NAME = "Testing";
const APP_MODE = AppEnum::APP_MODE_TESTING;

$dotEnvLoader = new DotEnvLoader(
    __DIR__ . '/../../.env'
);
$dotEnvLoader->load();

// Allow verbose mode when running tests: php tests/bin/run-test.php --verbose
$argv = $_SERVER['argv'] ?? [];
define('TEST_VERBOSE', in_array('--verbose', $argv, true) || in_array('-v', $argv, true));

$testOrmConfig = new Config();
$testOrmConfig->set('orm.use', true);

$testOrmConfig->set('orm.connection', [
    'driver'   => 'pdo_mysql',
    'user'     => $_ENV['DB_USER']     ?? '',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'dbname'   => $_ENV['DB_NAME']     ?? '',
    'host'     => $_ENV['DB_HOST']     ?? '',
    'charset'  => $_ENV['DB_CHARSET']  ?? 'utf8mb4',
]);

Application::configure(BASE_DIR, APP_NAME, APP_MODE)
    ->viaConfigOverrides([
        'orm' => $testOrmConfig,
    ])
    ->create();