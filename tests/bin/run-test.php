<?php
declare(strict_types=1);
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

// The runner prints its summary while tests are executing. Start the session
// before that output so requests using session-backed firewalls stay quiet.
if (session_status() === PHP_SESSION_NONE) {
    session_save_path('/private/tmp');
    session_start();
}

$testOrmConfig = new Config();
$testOrmConfig->set('orm.use', true);

$testOrmConfig->set('orm.connection', [
    // Each run gets an isolated database; no developer or production data is used.
    'driver' => 'pdo_sqlite',
    'memory' => true,
]);

// API routes receive a Redis dependency even when limiting is disabled.  The
// test kernel binds an unconnected client, so no external Redis is needed.
$_ENV['RATE_LIMITER_ENABLED'] = 'false';
putenv('RATE_LIMITER_ENABLED=false');
$_ENV['HASHING_IP_ALLOWLIST'] = '127.0.0.1';
putenv('HASHING_IP_ALLOWLIST=127.0.0.1');

$testAddonsConfig = new Config();
$testAddonsConfig->set('addons.enabled_addons', []);
$testAddonsConfig->set('addons.configs', []);

Application::configure(BASE_DIR, APP_NAME, APP_MODE)
    ->viaConfigOverrides([
        'orm' => $testOrmConfig,
        'addons' => $testAddonsConfig,
    ])
    ->create();
