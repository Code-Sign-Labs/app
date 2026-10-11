<?php
declare(strict_types=1);

use App\Controller\API\v1\CertificateController;
use App\Controller\API\v1\LicenseApiController;
use App\Controller\API\v2\StripeWebhookController;
use App\Controller\App\Billing\CustomersController;
use App\Controller\App\Billing\PaymentsController;
use App\Controller\App\Billing\SubscriptionsController;
use App\Controller\App\CertificatesController;
use App\Controller\App\HomeController;
use App\Controller\App\LicenseController;
use App\Controller\App\ProductController;
use App\Controller\App\SettingsController;
use App\Controller\App\UserController;
use App\Controller\App\WebhookController;
use App\Controller\AuthenticationController;
use App\Controller\NotFoundController;
use App\Firewall\IsLoggedFirewall;
use App\Firewall\IsNotLoggedFirewall;
use App\Firewall\RateLimiterFirewall;
use Framework\Router\RouteTable;

$routeTable = new RouteTable();

// Authentication routes

$routeTable->addRoute("/login", "GET", AuthenticationController::class . "@loginIndex", firewall: IsNotLoggedFirewall::class);
$routeTable->addRoute("/login", "POST", AuthenticationController::class . "@login", firewall: IsNotLoggedFirewall::class);
$routeTable->addRoute("/logout", "GET", AuthenticationController::class . "@logout", firewall: IsLoggedFirewall::class);

// Application routes

$routeTable->addRoute("/app/", "GET", HomeController::class . "@index", firewall: IsLoggedFirewall::class);

// Licenses

$routeTable->addRoute("/app/licenses", "GET", LicenseController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/license-new", "GET", LicenseController::class . "@createIndex", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/license-new", "POST", LicenseController::class . "@create", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/licenses/<licenseId>", "GET", LicenseController::class . "@details", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/licenses/<licenseId>/revoke", "GET", LicenseController::class . "@revoke", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/licenses/<licenseId>/suspend", "GET", LicenseController::class . "@suspend", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/licenses/<licenseId>/delete", "GET", LicenseController::class . "@delete", firewall: IsLoggedFirewall::class);

// Products

$routeTable->addRoute("/app/products", "GET", ProductController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products-new", "GET", ProductController::class . "@indexCreate", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products-new", "POST", ProductController::class . "@create", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products/<productSlug>/edit", "GET", ProductController::class . "@indexEdit", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products/<productSlug>/edit", "POST", ProductController::class . "@edit", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products/<productSlug>", "GET", ProductController::class . "@details", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products/<productSlug>/archive", "GET", ProductController::class . "@archive", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/products/<productSlug>/delete", "GET", ProductController::class . "@delete", firewall: IsLoggedFirewall::class);

// Webhooks

$routeTable->addRoute("/app/webhooks", "GET", WebhookController::class . "@index", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/webhooks-new", "GET", WebhookController::class . "@indexCreate", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/webhooks-new", "POST", WebhookController::class . "@create", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/webhooks/<webhookId>", "GET", WebhookController::class . "@details", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/webhooks/<webhookId>/delete", "GET", WebhookController::class . "@delete", firewall: IsLoggedFirewall::class);

// Users

$routeTable->addRoute("/app/users", "GET", UserController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users-new", "GET", UserController::class . "@createIndex", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users-new", "POST", UserController::class . "@create", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users/<userId>", "GET", UserController::class . "@details", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users/<userId>/delete", "GET", UserController::class . "@delete", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users/<userId>/edit", "GET", UserController::class . "@editIndex", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/users/<userId>/edit", "POST", UserController::class . "@edit", firewall: IsLoggedFirewall::class);

// Settings

$routeTable->addRoute("/app/settings", "GET", SettingsController::class . "@index", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/settings", "POST", SettingsController::class . "@update", firewall: IsLoggedFirewall::class);

// Certificates

$routeTable->addRoute("/app/certs", "GET", CertificatesController::class . "@index", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/certs", "POST", CertificatesController::class . "@rotate", firewall: IsLoggedFirewall::class);

// Billing

$routeTable->addRoute("/app/billing/customers", "GET", CustomersController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/customers-new", "GET", CustomersController::class . "@indexCreate", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/customers-new", "POST", CustomersController::class . "@create", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/customers/<id>", "GET", CustomersController::class . "@details", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/customers/<id>/edit", "GET", CustomersController::class . "@indexEdit", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/customers/<id>/edit", "POST", CustomersController::class . "@edit", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/customers/<id>/delete", "GET", CustomersController::class . "@delete", firewall: IsLoggedFirewall::class);

$routeTable->addRoute("/app/billing/subscriptions", "GET", SubscriptionsController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/subscriptions/<subscriptionId>", "GET", SubscriptionsController::class . "@details", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/subscriptions/<subscriptionId>/cancel", "POST", SubscriptionsController::class . "@cancel", firewall: IsLoggedFirewall::class);

$routeTable->addRoute("/app/billing/payments", "GET", PaymentsController::class . "@list", firewall: IsLoggedFirewall::class);
$routeTable->addRoute("/app/billing/payments/<invoiceId>/refund", "POST", PaymentsController::class . "@refund", firewall: IsLoggedFirewall::class);

// Public API routes

$routeTable->addRoute("/api/v1/public-key", "GET", CertificateController::class . "@getPublicKey", firewall: RateLimiterFirewall::class);
$routeTable->addRoute("/api/v1/sign", "POST", CertificateController::class . "@signHash", firewall: RateLimiterFirewall::class);

$routeTable->addRoute("/api/v1/licenses/<license_key>", "GET", LicenseApiController::class . "@validate", firewall: RateLimiterFirewall::class);
$routeTable->addRoute("/api/v1/licenses/<license_key>/devices", "GET", LicenseApiController::class . "@devices", firewall: RateLimiterFirewall::class);
$routeTable->addRoute("/api/v1/licenses/<license_key>/activate", "POST", LicenseApiController::class . "@activate", firewall: RateLimiterFirewall::class);
$routeTable->addRoute("/api/v1/licenses/<license_key>/deactivate", "POST", LicenseApiController::class . "@deactivate", firewall: RateLimiterFirewall::class);

$routeTable->addRoute("/api/v2/billing/stripe/webhook", "POST", StripeWebhookController::class . "@webhook");

$routeTable->setNotFoundController(NotFoundController::class);

return $routeTable;
