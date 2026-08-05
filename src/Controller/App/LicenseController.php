<?php

namespace App\Controller\App;

use App\Controller\CoreAbstractController;
use App\Entity\Activation;
use App\Entity\LicenseKey;
use App\Entity\Product;
use App\Enum\EventNameEnum;
use App\Enum\LicenseKeyStatusEnum;
use App\Enum\LicenseKeyTypeEnum;
use App\Service\ConfigService;
use App\Service\EventBusService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Random\RandomException;
use Throwable;

class LicenseController extends CoreAbstractController
{
    protected CSRFValidator $CSRFValidator;
    protected ConfigService $configService;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager)
    {
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
        $currentPage = max(1, (int) $request->getQuery("page", 1));
        $perPage = 10;

        $criteria = [];

        if($statusFilter !== "all") {
            $statuses = array_map(static fn (LicenseKeyStatusEnum $status) => $status->value, LicenseKeyStatusEnum::cases());
            if(in_array($statusFilter, $statuses, true)) {
                $criteria["status"] = $statusFilter;
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
        ], static fn ($value) => $value !== null && $value !== '');

        $licenses = $licenseRepository->findBy($criteria, ['createdAt' => 'DESC'], limit: $perPage, offset: $offset);

        return $this->render("app/license/list.twig", [
            'user' => $user,
            'products' => $products,
            'licenses' => $licenses,
            'success' => $success,
            'filters' => [
                'product' => $productFilter,
                'status' => $statusFilter,
                'type' => $typeFilter,
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
        ]);
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

        if(!$product || !$type) {
            $this->setFlash($request, "license.create.error", "Product and type are required.");
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

        $maxActivations = $request->getBody("max_activations");
        $expiresAt = $request->getBody("expires_at");
        $notes = $request->getBody("notes");


        try {
            $licenseKey = new LicenseKey();
            $licenseKey->setProduct($product)
                ->setType(LicenseKeyTypeEnum::from($type))
                ->setMaxActivations($maxActivations ?? 1)
                ->setExpiresAt($expiresAt ? new DateTime($expiresAt) : null)
                ->setNotes($notes)
                ->setStatus(LicenseKeyStatusEnum::active)
                ->setKey($this->generateLicenseKey());

            $this->entityManager->persist($licenseKey);
            $this->entityManager->flush();

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
            ], "License with id {$licenseKey->getId()} has been created", $user);

            $this->setFlash($request, "license.list.success", "License key created successfully. License key: {$licenseKey->getKey()}");
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
            ], "License has failed to be created", $user);

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