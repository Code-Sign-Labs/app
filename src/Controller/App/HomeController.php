<?php
declare(strict_types=1);
namespace App\Controller\App;

use App\Controller\CoreAbstractController;
use App\Entity\Activation;
use App\Entity\AuditLog;
use App\Entity\LicenseKey;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\ProductStatusEnum;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

class HomeController extends CoreAbstractController
{
    public function index(Request $request): Response
    {
        $user = $request->getAttributes()['user'];

        $licenseRepository = $this->entityManager->getRepository(LicenseKey::class);
        $productRepository = $this->entityManager->getRepository(Product::class);
        $userRepository = $this->entityManager->getRepository(User::class);
        $activationRepository = $this->entityManager->getRepository(Activation::class);
        $auditLogRepository = $this->entityManager->getRepository(AuditLog::class);

        return $this->render('app/home.twig', [
            'user' => $user,
            'license_count' => $licenseRepository->count(),
            'products_count' => $productRepository->count([
                'status' => ProductStatusEnum::active
            ]),
            'users_count' => $userRepository->count(),
            'activation_count' => $activationRepository->count(),
            'licenses' => $licenseRepository->findBy([], ['createdAt' => 'DESC'], limit: 8),
            'audit_logs' => $auditLogRepository->findBy([], ['createdAt' => 'DESC'], limit: 6),
        ]);
    }
}