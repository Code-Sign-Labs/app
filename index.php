<?php
declare(strict_types=1);
use Framework\AppEnum;
use Framework\Application;

define("FRAMEWORK_START", microtime(true));

require __DIR__ . '/vendor/autoload.php';

const BASE_DIR = __DIR__ . '/';
const APP_NAME = 'Code Sign App';
const APP_MODE = AppEnum::APP_MODE_NORMAL;

ini_set('upload_max_filesize', '5M');

Application::configure(BASE_DIR, APP_NAME, APP_MODE)
    ->viaRouting(
        webPath: BASE_DIR . 'configs/routes.php',
        consolePath: BASE_DIR . 'configs/console.php',
    )
    ->create();
