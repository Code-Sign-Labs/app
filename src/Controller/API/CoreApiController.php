<?php
declare(strict_types=1);
namespace App\Controller\API;

use App\Service\AuditLogService;
use App\Service\EventBusService;
use Doctrine\ORM\EntityManager;
use Framework\Http\AbstractController;
use Framework\Http\Objects\Response;
use Framework\Http\ViewEngine\ViewEngineInterface;

class CoreApiController extends AbstractController
{
    public AuditLogService $auditLogService;
    public EventBusService $eventBusService;
    public function __construct(ViewEngineInterface $viewEngine, public EntityManager $entityManager)
    {
        parent::__construct($viewEngine);
        $this->auditLogService = new AuditLogService($this->entityManager);
        $this->eventBusService = new EventBusService($this->entityManager);
    }

    public function success(array $data = []): Response
    {
        return $this->json([
            'success' => true,
            'response' => $data,
            'error' => null,
            'metadata' => [
                'timestamp' => microtime(true),
                'timezone' => date_default_timezone_get(),
            ]
        ]);
    }

    public function error(string $errorCode, string $errorDesc, int $code, array $errorData = []): Response
    {
        return $this->json([
            'success' => false,
            'response' => [],
            'error' => [
                'code' => $errorCode,
                'description' => $errorDesc,
                'data' => $errorData,
            ],
            'metadata' => [
                'timestamp' => microtime(true),
                'timezone' => date_default_timezone_get(),
            ]
        ], $code);
    }
}