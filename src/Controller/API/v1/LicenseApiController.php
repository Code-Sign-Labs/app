<?php
declare(strict_types=1);
namespace App\Controller\API\v1;

use App\Controller\API\CoreApiController;
use App\Entity\Activation;
use App\Entity\LicenseKey;
use App\Enum\EventNameEnum;
use App\Enum\LicenseKeyStatusEnum;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\ViewEngine\ViewEngineInterface;

class LicenseApiController extends CoreApiController
{
    protected EntityRepository $licenseRepository;
    public function __construct(ViewEngineInterface $viewEngine, public EntityManager $entityManager)
    {
        parent::__construct($viewEngine, $this->entityManager);
        $this->licenseRepository = $this->entityManager->getRepository(LicenseKey::class);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function validate(Request $request): Response
    {
        $licenseKey = $request->getParams()["license_key"];
        if(!$licenseKey) {
            return $this->error("LICENSE_KEY_MISSING", "License key is missing", 400);
        }

        $license = $this->licenseRepository->findOneBy([
            'key' => $licenseKey,
        ]);
        if(!$license) {
            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_AUTHORIZATION_FAILURE, [
                'userAgent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
                'key' => $licenseKey,
                'reason' => "LICENSE_NOT_FOUND"
            ]);
            return $this->error("LICENSE_NOT_FOUND", "License key not found", 404);
        }

        if($license->getStatus() !== LicenseKeyStatusEnum::active) {
            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_AUTHORIZATION_FAILURE, [
                'userAgent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
                'licenseId' => $license->getId(),
                'reason' => "LICENSE_INACTIVE"
            ]);
            return $this->error("LICENSE_INACTIVE", "License key is not active", 403);
        }

        if($license->getExpiresAt() && $license->getExpiresAt() < new DateTime()) {
            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_AUTHORIZATION_FAILURE, [
                'userAgent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
                'licenseId' => $license->getId(),
                'reason' => "LICENSE_EXPIRED"
            ]);
            return $this->error("LICENSE_EXPIRED", "License key has expired", 403);
        }

        $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_AUTHORIZATION_SUCCESS, [
            'userAgent' => $request->getHeader('User-Agent'),
            'ip' => $request->getUserIp(),
            'licenseId' => $license->getId()
        ]);

        return $this->success([
            'id' => $license->getId(),
            'product' => [
                'id' => $license->getProduct()->getId(),
                'name' => $license->getProduct()->getName(),
            ],
            'batch' => $license->getBatch() ? [
                'id' => $license->getBatch()->getId(),
            ] : null,
            'max_activations' => $license->getMaxActivations(),
            'current_activations' => $license->getCurrentActivations(),
            'expires_at' => $license->getExpiresAt() ? $license->getExpiresAt()->getTimestamp() : null,
            'created_at' => $license->getCreatedAt()->getTimestamp(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function devices(Request $request): Response
    {
        $licenseKey = $request->getParams()["license_key"];
        if(!$licenseKey) {
            return $this->error("LICENSE_KEY_MISSING", "License key is missing", 400);
        }

        $license = $this->licenseRepository->findOneBy([
            'key' => $licenseKey,
        ]);
        if(!$license) {
            return $this->error("LICENSE_NOT_FOUND", "License key not found", 404);
        }

        if($license->getStatus() !== LicenseKeyStatusEnum::active) {
            return $this->error("LICENSE_INACTIVE", "License key is not active", 403);
        }

        if($license->getExpiresAt() && $license->getExpiresAt() < new DateTime()) {
            return $this->error("LICENSE_EXPIRED", "License key has expired", 403);
        }

        $activations = [];
        /**
         * @var Activation $activation
         */
        foreach($license->getActivations() as $activation) {
            $activations[] = [
                'id' => $activation->getId(),
                'ip' => $activation->getIpAddress(),
                'device_name' => $activation->getDeviceName(),
                'device_identifier' => $activation->getDeviceIdentifier(),
                'metadata' => $activation->getMetadata(),
                'active' => $activation->isActive(),
                'deactivated_at' => $activation->getDeactivatedAt() ? $activation->getDeactivatedAt()->getTimestamp() : null,
                'created_at' => $activation->getCreatedAt()->getTimestamp(),
            ];
        }

        return $this->success([
            'activations' => $activations
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function activate(Request $request): Response
    {
        $licenseKey = $request->getParams()["license_key"];
        if(!$licenseKey) {
            return $this->error("LICENSE_KEY_MISSING", "License key is missing", 400);
        }

        $deviceName = $request->getBody('device_name');
        $deviceIdentifier = $request->getBody('device_identifier');

        if(!$deviceName || !$deviceIdentifier) {
            return $this->error("DEVICE_INFO_MISSING", "Device name or identifier is missing", 400);
        }

        $license = $this->licenseRepository->findOneBy([
            'key' => $licenseKey,
        ]);
        if(!$license) {
            return $this->error("LICENSE_NOT_FOUND", "License key not found", 404);
        }

        if($license->getStatus() !== LicenseKeyStatusEnum::active) {
            return $this->error("LICENSE_INACTIVE", "License key is not active", 403);
        }

        if($license->getExpiresAt() && $license->getExpiresAt() < new DateTime()) {
            return $this->error("LICENSE_EXPIRED", "License key has expired", 403);
        }

        if($license->getCurrentActivations() >= $license->getMaxActivations()) {
            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_ACTIVATION_FAILURE, [
                'userAgent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
                'licenseId' => $license->getId(),
            ]);

            return $this->error("LICENSE_MAX_ACTIVATIONS_REACHED", "License key has reached the maximum number of activations", 403);
        }

        $activation = new Activation();
        $activation->setDeviceName($deviceName)
            ->setDeviceIdentifier($deviceIdentifier)
            ->setActive(true)
            ->setLicenseKey($license)
            ->setIpAddress($request->getUserIp())
            ->setMetadata([
                'user_agent' => $request->getHeader('User-Agent'),
                'time_of_activation' => microtime(true),
            ]);

        $license->setCurrentActivations($license->getCurrentActivations() + 1);

        try {
            $this->entityManager->persist($activation);
            $this->entityManager->persist($license);
            $this->entityManager->flush();

            $this->auditLogService->log("license.activation.success", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp()
            ], "License with id {$license->getId()} has been activated successfully.", licenseKey: $license);

            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_ACTIVATION_SUCCESS, [
                'userAgent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
                'licenseId' => $license->getId(),
                'activationId' => $activation->getId(),
            ]);

            return $this->success([
                'activation' => [
                    'id' => $activation->getId(),
                    'ip' => $activation->getIpAddress(),
                    'device_name' => $activation->getDeviceName(),
                    'device_identifier' => $activation->getDeviceIdentifier(),
                    'metadata' => $activation->getMetadata(),
                    'active' => $activation->isActive(),
                    'created_at' => $activation->getCreatedAt()?->getTimestamp(),
                ],
                'license_id' => $license->getId()
            ]);
        } catch(\Throwable $e) {
            $this->auditLogService->log("license.activation.failure", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp()
            ], "License with id {$license->getId()} could not be activated.", licenseKey: $license);

            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_ACTIVATION_FAILURE, [
                'userAgent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
                'licenseId' => $license->getId(),
                'error' => $e->getMessage(),
            ]);

            return $this->error("INTERNAL_SERVER_ERROR", "Internal server error", 500);
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deactivate(Request $request): Response
    {
        $licenseKey = $request->getParams()["license_key"];
        if(!$licenseKey) {
            return $this->error("LICENSE_KEY_MISSING", "License key is missing", 400);
        }

        $license = $this->licenseRepository->findOneBy([
            'key' => $licenseKey,
        ]);
        if(!$license) {
            return $this->error("LICENSE_NOT_FOUND", "License key not found", 404);
        }

        if($license->getStatus() !== LicenseKeyStatusEnum::active) {
            return $this->error("LICENSE_INACTIVE", "License key is not active", 403);
        }

        if($license->getExpiresAt() && $license->getExpiresAt() < new DateTime()) {
            return $this->error("LICENSE_EXPIRED", "License key has expired", 403);
        }

        $activationId = $request->getBody("activation_id");
        if(!$activationId) {
            return $this->error("ACTIVATION_ID_MISSING", "Activation ID is missing", 400);
        }

        $activation = $this->entityManager->getRepository(Activation::class)->find($activationId);
        if(!$activation) {
            return $this->error("ACTIVATION_NOT_FOUND", "Activation not found", 404);
        }

        $license->setCurrentActivations(max($license->getCurrentActivations() - 1, 0));
        $activation->setActive(false);
        $activation->setDeactivatedAt(new DateTime());

        try {
            $this->entityManager->persist($activation);
            $this->entityManager->persist($license);
            $this->entityManager->flush();

            $this->auditLogService->log("license.deactivation.success", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp()
            ], "License with id {$license->getId()} has been deactivated successfully.", licenseKey: $license);

            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_ACTIVATION_REMOVE_SUCCESS, [
                'userAgent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
                'activationId' => $activation->getId(),
                'licenseId' => $license->getId(),
            ]);

            return $this->success([
                'activation' => [
                    'id' => $activation->getId(),
                    'active' => $activation->isActive(),
                    'deactivated_at' => $activation->getDeactivatedAt()->getTimestamp(),
                ]
            ]);
        } catch(\Throwable $e) {
            $this->auditLogService->log("license.deactivation.failure", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp()
            ], "License with id {$license->getId()} could not be deactivated.", licenseKey: $license);

            $this->eventBusService->triggerEvent(EventNameEnum::LICENSE_ACTIVATION_REMOVE_FAILURE, [
                'userAgent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
                'licenseId' => $license->getId(),
                'error' => $e->getMessage(),
            ]);

            return $this->error("INTERNAL_SERVER_ERROR", "Internal server error", 500);
        }
    }
}