<?php
declare(strict_types=1);
use Framework\Configs\Config;

$config = new Config();

$config->set("error.errorHandlers", [
	// Your error handlers
]);

return $config;