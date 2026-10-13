<?php
declare(strict_types=1);
namespace App\Controller\App;

use App\Controller\CoreAbstractController;
use App\Entity\Activation;
use App\Entity\Batch;
use App\Entity\Customer;
use App\Entity\LicenseKey;
use App\Entity\Product;
use App\Enum\EventNameEnum;
use App\Enum\LicenseKeyBillingTypeEnum;
use App\Enum\LicenseKeyStatusEnum;
use App\Enum\LicenseKeyTypeEnum;
use App\Service\ConfigService;
use App\Service\EventBusService;
use App\Service\MailingService;
use App\Service\StripeService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Framework\Messenger\MessageDispatcher;
use Random\RandomException;
use Throwable;

class LicenseController extends CoreAbstractController
{
    public const STRIPE_DEFAULT_CURRENCY = 'eur';

    protected StripeService $stripeService;
    protected CSRFValidator $CSRFValidator;
    protected ConfigService $configService;
    protected MailingService $mailingService;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager, protected MessageDispatcher $dispatcher)
    {
        $this->mailingService = new MailingService($this->dispatcher);
        $this->stripeService = new StripeService();
        $this->configService = new ConfigService($entityManager);
        $this->CSRFValidator = new CSRFValidator();
        parent::__construct($viewEngine, $entityManager);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $success = $this->getFlash($request, "license.list.success");

        $productRepository = $this->entityManager->getRepository(Product::class);
        $products = $productRepository->findAll();
        $licenseRepository = $this->entityManager->getRepository(LicenseKey::class);

        $productFilter = $request->getQuery("product", "all");
        $statusFilter = $request->getQuery("status", "all");
        $typeFilter = $request->getQuery("type", "all");
        $batchFilter = $request->getQuery("batch", "all");
        $currentPage = max(1, (int) $request->getQuery("page", 1));
        $perPage = 10;

        $criteria = [];

        if($statusFilter !== "all") {
            $statuses = array_map(static fn (LicenseKeyStatusEnum $status) => $status->value, LicenseKeyStatusEnum::cases());
            if(in_array($statusFilter, $statuses, true)) {
                $criteria["status"] = $statusFilter;
            }
        }

        if($batchFilter !== "all") {
            $batch = $this->entityManager->getRepository(Batch::class)->findBy([
                'id' => $batchFilter
            ]);

            if($batch) {
                $criteria["batch"] = $batch;
            } else {
                $batchFilter = "all";
            }
        }

        if($typeFilter !== "all") {
            $types = array_map(static fn (LicenseKeyTypeEnum $type) => $type->value, LicenseKeyTypeEnum::cases());
            if(in_array($typeFilter, $types, true)) {
                $criteria["type"] = $typeFilter;
            }
        }

        if($productFilter !== 'all') {
            $product = $productRepository->findOneBy([
                'slug' => $productFilter
            ]);

            if($product) {
                $criteria["product"] = $product;
            } else {
                $productFilter = "all";
            }
        }

        $totalLicenses = $licenseRepository->count($criteria);
        $totalPages = max(1, (int) ceil($totalLicenses / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        $queryParameters = array_filter([
            'product' => $productFilter !== 'all' ? $productFilter : null,
            'status' => $statusFilter !== 'all' ? $statusFilter : null,
            'type' => $typeFilter !== 'all' ? $typeFilter : null,
            'batch' => $batchFilter !== 'all' ? $batchFilter : null,
        ], static fn ($value) => $value !== null && $value !== '');

        $licenses = $licenseRepository->findBy($criteria, ['createdAt' => 'DESC'], limit: $perPage, offset: $offset);

        return $this->render("app/license/list.twig", [
            'user' => $user,
            'products' => $products,
            'licenses' => $licenses,
            'success' => $success,
            'batches' => $this->entityManager->getRepository(Batch::class)->findAll(),
            'filters' => [
                'product' => $productFilter,
                'status' => $statusFilter,
                'type' => $typeFilter,
                'batch' => $batchFilter,
            ],
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalLicenses,
                'from' => $totalLicenses === 0 ? 0 : $offset + 1,
                'to' => $totalLicenses === 0 ? 0 : min($offset + $perPage, $totalLicenses),
                'previousUrl' => $currentPage > 1 ? $this->buildPaginationUrl($queryParameters, $currentPage - 1) : null,
                'nextUrl' => $currentPage < $totalPages ? $this->buildPaginationUrl($queryParameters, $currentPage + 1) : null,
                'pages' => $this->buildPaginationPages($queryParameters, $currentPage, $totalPages),
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function createIndex(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "license.create.error");
        $productSlug = $request->getQuery("product", "all");

        $productRepository = $this->entityManager->getRepository(Product::class);
        $productId = null;
        if($productSlug !== "all") {
            $product = $productRepository->findOneBy([
                'slug' => $productSlug
            ]);
            if($product) {
                $productId = $product->getId();
            }
        }

        return $this->render("app/license/create.twig", [
            'user' => $user,
            'error' => $error,
            'products' => $productRepository->findAll(),
            'productId' => $productId,
            'customers' => $this->entityManager->getRepository(Customer::class)->findAll(),
        ]);
    }

    /**
     * @param string $dateString
     * @return DateTime
     * @throws \Exception
     */
    protected function parseDate(string $dateString): DateTime
    {
        $dateString = trim($dateString);

        // Format: YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return DateTime::createFromFormat('Y-m-d', $dateString);
        }

        // Format: YYYY-MM-DD HH:MM:SS
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
            return DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        }

        // Format: DD/MM/YYYY
        if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $dateString)) {
            $date = DateTime::createFromFormat('d/m/Y', $dateString);
            $date->setTime(0, 0, 0);
            return $date;
        }

        // Format: MM/DD/YYYY
        if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $dateString)) {
            $date = DateTime::createFromFormat('m/d/Y', $dateString);
            $date->setTime(0, 0, 0);
            return $date;
        }

        $date = new DateTime($dateString);
        if (!$date) {
            throw new \Exception("Invalid date format: " . $dateString);
        }

        return $date;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create(Request $request): Response
    {
        if(!$this->CSRFValidator->handle($request)) {
            return $this->redirect("/app/license-new");
        }
        $user = $request->getAttributes()['user'];
        $product = $request->getBody("product");
        $type = $request->getBody("type");
        $customerId = $request->getBody("customer_id");

        if(!$product || !$type || !$customerId) {
            $this->setFlash($request, "license.create.error", "Product, customer and type are required.");
            return $this->redirect("/app/license-new");
        }

        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $customer = $customerRepository->findOneBy([
            'id' => $customerId
        ]);
        if(!$customer || !$customer->getStripeCustomerId()) {
            $this->setFlash($request, "license.create.error", "Customer not found.");
            return $this->redirect("/app/license-new");
        }

        $productRepository = $this->entityManager->getRepository(Product::class);
        $product = $productRepository->findOneBy([
            'id' => $product
        ]);
        if(!$product) {
            $this->setFlash($request, "license.create.error", "Product not found.");
            return $this->redirect("/app/license-new");
        }

        $maxActivations = intval($request->getBody("max_activations", 1));
        $trialExpiration = intval($request->getBody("trial_expiration", 0)); // days
        $billingType = $request->getBody("billing_type");
        if($type !== "perpetual" && !$billingType) {
            $this->setFlash($request, "license.create.error", "Billing type is required for non-perpetual licenses.");
            return $this->redirect("/app/license-new");
        }
        if($billingType !== 'day' && $billingType !== 'week' && $billingType !== 'month' && $billingType !== 'year') {
            $this->setFlash($request, "license.create.error", "Billing type must be one of: day, week, month, year.");
            return $this->redirect("/app/license-new");
        }

        $notes = $request->getBody("notes");

        $batchName = $request->getbody("batch");
        $batchQuantity = $request->getbody("batch_quantity");

        try {
            $batch = null;
            $licenseKey = null;

            $productStripe = $this->stripeService->getProduct($product->getStripeProductId());
            $price = $this->stripeService->getPrice($productStripe->default_price);

            if(LicenseKeyTypeEnum::from($type) === LicenseKeyTypeEnum::subscription) {
                $subscription = $this->stripeService->getClient()->subscriptions->create([
                    'customer' => $customer->getStripeCustomerId(),
                    'items' => [
                        [
                            'price_data' => [
                                'unit_amount' => $price->unit_amount,
                                'currency' => $_ENV["STRIPE_DEFAULT_CURRENCY"] ?? self::STRIPE_DEFAULT_CURRENCY,
                                'product' => $product->getStripeProductId(),
                                'recurring' => [
                                    'interval' => $billingType,
                                ],
                            ]
                        ]
                    ],
                    'metadata' => [
                        'product_id' => $product->getId(),
                    ]
                ]);
            }

            if(LicenseKeyTypeEnum::from($type) === LicenseKeyTypeEnum::trial) {
                $subscription = $this->stripeService->getClient()->subscriptions->create([
                    'customer' => $customer->getStripeCustomerId(),
                    'items' => [
                        [
                            'price_data' => [
                                'unit_amount' => $price->unit_amount,
                                'currency' => $_ENV["STRIPE_DEFAULT_CURRENCY"] ?? self::STRIPE_DEFAULT_CURRENCY,
                                'product' => $product->getStripeProductId(),
                                'recurring' => [
                                    'interval' => $billingType
                                ],
                            ]
                        ]
                    ],
                    'metadata' => [
                        'product_id' => $product->getId(),
                    ],
                    'trial_period_days' => $trialExpiration,
                ]);
            }

            if($batchName && $batchQuantity) {
                $batch = new Batch();
                $batch->setLabel($batchName)
                    ->setType(LicenseKeyTypeEnum::from($type))
                    ->setProduct($product)
                    ->setExportedAt(new DateTime())
                    ->setMaxActivations($maxActivations)
                    ->setQuantity((int) $batchQuantity);
                $this->entityManager->persist($batch);

                for($i = 0; $i < $batchQuantity; $i++) {
                    $licenseKey = new LicenseKey();
                    $licenseKey->setProduct($product)
                        ->setType(LicenseKeyTypeEnum::from($type))
                        ->setMaxActivations($maxActivations)
                        ->setExpiresAt(null) // Needs to be calculated based on time when the license is activated
                        ->setNotes($notes)
                        ->setStatus(LicenseKeyStatusEnum::active)
                        ->setKey($this->generateLicenseKey())
                        ->setStripeSubscriptionId($subscription->id ?? null)
                        ->setBatch($batch)
                        ->setCustomer($customer)
                        ->setBillingType(LicenseKeyBillingTypeEnum::from($billingType));

                    if(LicenseKeyTypeEnum::from($type) === LicenseKeyTypeEnum::subscription) {
                        $licenseKey->setStatus(LicenseKeyStatusEnum::awaiting_payment);
                    }

                    $this->entityManager->persist($licenseKey);
                }
            } else {
                $licenseKey = new LicenseKey();
                $licenseKey->setProduct($product)
                    ->setType(LicenseKeyTypeEnum::from($type))
                    ->setMaxActivations($maxActivations)
                    ->setExpiresAt(null) // Needs to be calculated based on time when the license is activated
                    ->setNotes($notes)
                    ->setStatus(LicenseKeyStatusEnum::active)
                    ->setStripeSubscriptionId($subscription->id ?? null)
                    ->setKey($this->generateLicenseKey())
                    ->setCustomer($customer)
                    ->setBillingType(LicenseKeyBillingTypeEnum::from($billingType));

                if(LicenseKeyTypeEnum::from($type) === LicenseKeyTypeEnum::subscription) {
                    $licenseKey->setStatus(LicenseKeyStatusEnum::awaiting_payment);
                }

                $this->entityManager->persist($licenseKey);
            }

            $this->entityManager->flush();

            if($batch) {
                $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_CREATE_SUCCESS, [
                    "batchId" => $batch->getId(),
                    "productId" => $product->getId(),
                    "author" => $user->getEmail(),
                    "ip" => $request->getUserIp(),
                    "userAgent" => $request->getHeader("User-Agent"),
                ]);

                $this->auditLogService->log("license.create.success", [
                    'user_agent' => $request->getHeader("User-Agent"),
                    'ip' => $request->getUserIp()
                ], "Batch with id {$batch->getId()} has been created", $user, product: $product);
                $this->setFlash($request, "license.list.success", "License keys created successfully. Batch: {$batch->getLabel()}");

                $this->mailingService->sendMail(
                    "email/batch_created",
                    "New License Batch Created",
                    $customer->getEmail(),
                    [
                        'batch' => [
                            'maxActivations' => $batch->getMaxActivations(),
                            'quantity' => $batch->getQuantity(),
                            'licenseKeys' => array_map(static fn (LicenseKey $licenseKey) => [
                                'licenseKey' => $licenseKey->getKey(),
                                'status' => ['value' => $licenseKey->getStatus()->value],
                            ], $batch->getLicenseKeys()->toArray()),
                        ],
                        'product' => [
                            'name' => $product->getName(),
                        ],
                        'customer' => [
                            'label' => $customer->getLabel(),
                        ],
                    ]
                );
            } else {
                $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_CREATE_SUCCESS, [
                    "licenseKey" => $licenseKey->getKey(),
                    "productId" => $product->getId(),
                    "author" => $user->getEmail(),
                    "ip" => $request->getUserIp(),
                    "userAgent" => $request->getHeader("User-Agent"),
                ]);

                $this->auditLogService->log("license.create.success", [
                    'user_agent' => $request->getHeader("User-Agent"),
                    'ip' => $request->getUserIp()
                ], "License with id {$licenseKey->getId()} has been created", $user, product: $product);
                $this->setFlash($request, "license.list.success", "License key created successfully. License key: {$licenseKey->getKey()}");

                $this->mailingService->sendMail(
                    "email/license_created",
                    "New License Key Created",
                    $customer->getEmail(),
                    [
                        'license' => [
                            'maxActivations' => $licenseKey->getMaxActivations(),
                            'licenseKey' => $licenseKey->getKey(),
                            'status' => ['value' => $licenseKey->getStatus()->value],
                        ],
                        'product' => [
                            'name' => $product->getName(),
                        ],
                        'customer' => [
                            'label' => $customer->getLabel(),
                        ],
                    ]
                );
            }

            return $this->redirect("/app/licenses");
        } catch (Throwable $e) {
            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_CREATE_FAILURE, [
                "productId" => $product->getId(),
                "author" => $user->getEmail(),
                "ip" => $request->getUserIp(),
                "userAgent" => $request->getHeader("User-Agent"),
            ]);

            $this->auditLogService->log("license.create.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "License has failed to be created", $user, product: $product);

            $this->setFlash($request, "license.create.error", "An error occurred while creating the license key: " . $e->getMessage());
            return $this->redirect("/app/license-new");
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function details(Request $request): Response
    {
        $licenseId = $request->getParams()['licenseId'];
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "licenses.details.error");

        $licenseRepository = $this->entityManager->getRepository(LicenseKey::class);
        $license = $licenseRepository->findOneBy([
            'id' => $licenseId
        ]);
        if(!$license) {
            return $this->redirect("/app/licenses");
        }

        $activationsRepository = $this->entityManager->getRepository(Activation::class);
        $activations = $activationsRepository->findBy([
            'licenseKey' => $license
        ], [
            'active' => 'DESC'
        ]);

        return $this->render("app/license/details.twig", [
            'user' => $user,
            'license' => $license,
            'logs' => $this->auditLogService->getLicenseKeyLogs($license),
            'activations' => $activations,
            'error' => $error,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function revoke(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $licenseId = $request->getParams()['licenseId'];

        $licenseRepository = $this->entityManager->getRepository(LicenseKey::class);
        $license = $licenseRepository->findOneBy([
            'id' => $licenseId
        ]);

        if(!$license) {
            return $this->redirect("/app/licenses");
        }

        $license->setStatus($license->getStatus() === LicenseKeyStatusEnum::revoked ? LicenseKeyStatusEnum::active : LicenseKeyStatusEnum::revoked);
        $action = $license->getStatus() === LicenseKeyStatusEnum::revoked ? "revoked" : "unrevoked";
        try {
            $this->entityManager->persist($license);
            $this->entityManager->flush();

            $this->auditLogService->log("license.revoke.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "License with id {$licenseId} has been {$action}", $user, $license);

            $event = $action == "revoked" ? EventNameEnum::LICENSE_STATUS_REVOKE : EventNameEnum::LICENSE_STATUS_UNREVOKE;

            $this->eventBusService->triggerEvent($event, [
                'userAgent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp(),
                'licenseId' => $licenseId,
                "authorId" => $user->getId(),
                "authorEmail" => $user->getEmail(),
            ]);
        } catch (Throwable $e) {
            $this->auditLogService->log("license.revoke.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "License with id {$licenseId} could not be {$action}", $user, $license);
        }

        return $this->redirect("/app/licenses/{$licenseId}");
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function suspend(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $licenseId = $request->getParams()['licenseId'];

        $licenseRepository = $this->entityManager->getRepository(LicenseKey::class);
        $license = $licenseRepository->findOneBy([
            'id' => $licenseId
        ]);

        if(!$license) {
            return $this->redirect("/app/licenses");
        }

        $license->setStatus($license->getStatus() === LicenseKeyStatusEnum::suspended ? LicenseKeyStatusEnum::active : LicenseKeyStatusEnum::suspended);
        $action = $license->getStatus() === LicenseKeyStatusEnum::suspended ? "suspended" : "unsuspended";
        try {
            $this->entityManager->persist($license);
            $this->entityManager->flush();

            $this->auditLogService->log("license.suspend.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "License with id {$licenseId} has been {$action}", $user, $license);

            $event = $action === "suspended" ? EventNameEnum::LICENSE_STATUS_SUSPEND : EventNameEnum::LICENSE_STATUS_UNSUSPEND;

            $this->eventBusService->triggerEvent($event, [
                'userAgent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp(),
                'licenseId' => $licenseId,
                "authorId" => $user->getId(),
                "authorEmail" => $user->getEmail(),
            ]);
        } catch (Throwable $e) {
            $this->auditLogService->log("license.suspend.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "License with id {$licenseId} could not be {$action}", $user, $license);
        }
        return $this->redirect("/app/licenses/{$licenseId}");
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $licenseId = $request->getParams()['licenseId'];

        $licenseRepository = $this->entityManager->getRepository(LicenseKey::class);
        $license = $licenseRepository->findOneBy([
            'id' => $licenseId
        ]);
        if(!$license) return $this->redirect("/app/licenses");

        try {
            $this->entityManager->remove($license);
            $this->entityManager->flush();

            $this->auditLogService->log("license.delete.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "License with id {$licenseId} has been deleted", $user);

            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_DELETE_SUCCESS, [
                'userAgent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp(),
                'licenseId' => $licenseId,
                "authorId" => $user->getId(),
                "authorEmail" => $user->getEmail(),
            ]);
        } catch (Throwable $e) {
            $this->auditLogService->log("license.delete.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "License with id {$licenseId} could not be deleted", $user);

            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_DELETE_FAILURE, [
                'userAgent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp(),
                'licenseId' => $licenseId,
                "authorId" => $user->getId(),
                "authorEmail" => $user->getEmail(),
            ]);

            $this->setFlash($request, "licenses.details.error", "Something went wrong: " . $e->getMessage());
        }
        return $this->redirect("/app/licenses");
    }

    /**
     * @return string
     * @throws RandomException
     */
    protected function generateLicenseKey(): string
    {
        $prefix = $this->configService->getConfig()->getKeyPrefix();
        $pattern = $this->configService->getConfig()->getKeyPattern();

        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxIndex = strlen($characters) - 1;

        return $prefix . "-" . preg_replace_callback('/X/', function () use ($characters, $maxIndex) {
            return $characters[random_int(0, $maxIndex)];
        }, $pattern);
    }
}