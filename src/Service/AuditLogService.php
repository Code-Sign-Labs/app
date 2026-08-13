<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\LicenseKey;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Throwable;

class AuditLogService
{
    protected EntityRepository $auditLogsRepository;
    public function __construct(protected EntityManager $entityManager)
    {
        $this->auditLogsRepository = $this->entityManager->getRepository(AuditLog::class);
    }

    /**
     * @param string $event
     * @param array $data
     * @param string $desc
     * @param User|null $user
     * @param LicenseKey|null $licenseKey
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function log(string $event, array $data, string $desc, ?User $user = null, ?LicenseKey $licenseKey = null, ?Product $product = null): void
    {
        $log = new AuditLog();
        $log->setEvent($event)
            ->setUser($user)
            ->setLicenseKey($licenseKey)
            ->setMetadata($data)
            ->setProduct($product)
            ->setDescription($desc);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * @param LicenseKey $licenseKey
     * @param int $limit
     * @return LicenseKey[]
     */
    public function getLicenseKeyLogs(LicenseKey $licenseKey, int $limit = 5): array
    {
        return $this->auditLogsRepository->findBy([
            'licenseKey' => $licenseKey,
        ], orderBy: [
            'createdAt' => 'DESC'
        ], limit: $limit);
    }

    /**
     * @param Product $product
     * @param int $limit
     * @return Product[]
     */
    public function getProductLogs(Product $product, int $limit = 5): array
    {
        return $this->auditLogsRepository->findBy([
            'product' => $product,
        ], orderBy: [
            'createdAt' => 'DESC'
        ], limit: $limit);
    }
}