<?php

use App\Controller\App\HomeController;
use App\Controller\App\LicenseController;
use App\Controller\App\ProductController;
use App\Controller\App\SettingsController;
use App\Controller\App\UserController;
use App\Controller\AuthenticationController;
use App\Controller\NotFoundController;
use App\Firewall\IsLoggedFirewall;
use App\Firewall\IsNotLoggedFirewall;
use Framework\Router\RouteTable;

$routeTable = new RouteTable();

$routeTable->addRoute("/login", "GET", AuthenticationController::class . "@loginIndex", firewall: IsNotLoggedFirewall::class);
$routeTable->addRoute("/login", "POST", AuthenticationController::class . "@login", firewall: IsNotLoggedFirewall::class);
$routeTable->addRoute("/logout", "GET", AuthenticationController::class . "@logout", firewall: IsLoggedFirewall::class);

$routeTable->addRoute("/app/", "GET", HomeController::class . "@index", firewall: IsLoggedFirewall::class);

$routeTable->addRoute("/app/licenses", "GET", LicenseController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/licenses/<licenseId>", "GET", LicenseController::class . "@details", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/licenses/<licenseId>/revoke", "GET", LicenseController::class . "@revoke", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/licenses/<licenseId>/suspend", "GET", LicenseController::class . "@suspend", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/license-new", "GET", LicenseController::class . "@createIndex", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/license-new", "POST", LicenseController::class . "@create", firewall: IsLoggedFirewall::class);

$routeTable->addRoute("/app/products", "GET", ProductController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products-new", "GET", ProductController::class . "@indexCreate", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products-new", "POST", ProductController::class . "@create", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products/<productSlug>/edit", "GET", ProductController::class . "@indexEdit", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products/<productSlug>/edit", "POST", ProductController::class . "@edit", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products/<productSlug>", "GET", ProductController::class . "@details", firewall: IsLoggedFirewall::class);

$routeTable->addRoute("/app/users", "GET", UserController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users-new", "GET", UserController::class . "@createIndex", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users-new", "POST", UserController::class . "@create", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users/<userId>", "GET", UserController::class . "@details", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users/<userId>/delete", "GET", UserController::class . "@delete", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users/<userId>/edit", "GET", UserController::class . "@editIndex", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users/<userId>/edit", "POST", UserController::class . "@edit", firewall: IsLoggedFirewall::class);

$routeTable->addRoute("/app/settings", "GET", SettingsController::class . "@index", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/settings", "POST", SettingsController::class . "@update", firewall: IsLoggedFirewall::class);

$routeTable->setNotFoundController(NotFoundController::class);

return $routeTable;
