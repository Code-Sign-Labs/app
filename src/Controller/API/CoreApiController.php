<?php
declare(strict_types=1);
namespace App\Controller\API;

use App\Service\AuditLogService;
use App\Service\EventBusService;
use Doctrine\ORM\EntityManager;
use Framework\Http\AbstractController;
use Framework\Http\Objects\Response;
use Framework\Http\ViewEngine\ViewEngineInterface;
use RuntimeException;
use SodiumException;

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

    /**
     * @param string $message
     * @return string Base64 encoded signature
     * @throws SodiumException
     */
    protected function signResponse(string $message): string
    {
        $keyPath = __DIR__ . "/../../../storage/keys/master_private.pem";
        $privateKeyPem = file_get_contents($keyPath);

        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if (!$privateKey) {
            throw new \RuntimeException("Invalid PEM private key");
        }

        openssl_sign($message, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($binarySignature);
    }

    public function success(array $data = []): Response
    {
        $response = new Response();
        $response->setHeaders([
            'Content-Type' => 'application/json',
        ]);

        $payloadJson = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response->setBody(json_encode([
            'success' => true,
            'response' => $data,
            'error' => null,
            'metadata' => [
                'timestamp' => microtime(true),
                'timezone' => date_default_timezone_get(),
            ],
            'signature' => $this->signResponse($payloadJson)
        ]));

        return $response;
    }

    public function error(string $errorCode, string $errorDesc, int $code = 400, array $errorData = []): Response
    {
        $response = new Response();
        $response->setHeaders([
            'Content-Type' => 'application/json',
        ]);
        $response->setStatusCode($code);

        $errorPayload = [
            'code' => $errorCode,
            'description' => $errorDesc,
            'data' => $errorData,
        ];

        $payloadJson = json_encode($errorPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response->setBody(json_encode([
            'success' => false,
            'response' => [],
            'error' => $errorPayload,
            'metadata' => [
                'timestamp' => microtime(true),
                'timezone' => date_default_timezone_get(),
            ],
            'signature' => $this->signResponse($payloadJson)
        ]));

        return $response;
    }
}