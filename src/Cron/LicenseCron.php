<?php
declare(strict_types=1);

namespace App\Cron;

use App\Entity\LicenseKey;
use App\Enum\LicenseKeyStatusEnum;
use Doctrine\ORM\EntityManager;

class LicenseCron
{
    public function __construct(protected EntityManager $entityManager)
    {
    }

    public function run(): void
    {
        $licenses = $this->entityManager->getRepository(LicenseKey::class)->findBy([
            'status' => LicenseKeyStatusEnum::awaiting_payment
        ]);
        foreach ($licenses as $license) {
            if($license->getCreatedAt()->modify('+7 day') < new \DateTime()) {
                $license->setStatus(LicenseKeyStatusEnum::revoked);
                $license->setUpdatedAt(new \DateTime());
                $this->entityManager->persist($license);
            }
        }

        $licenses = $this->entityManager->getRepository(LicenseKey::class)->findBy([
            'status' => LicenseKeyStatusEnum::revoked
        ]);
        foreach ($licenses as $license) {
            if($license->getUpdatedAt()->modify('+30 day') < new \DateTime()) {
                $this->entityManager->remove($license);
            }
        }

        $licenses = $this->entityManager->getRepository(LicenseKey::class)->findAll();
        foreach ($licenses as $license) {
            if($license->getExpiresAt() && $license->getExpiresAt() < new \DateTime()) {
                $license->setStatus(LicenseKeyStatusEnum::expired);
                $license->setUpdatedAt(new \DateTime());
                $this->entityManager->persist($license);
            }
        }

        $this->entityManager->flush();
    }
}