<?php
declare(strict_types=1);

namespace App\Controller\App;

use App\Controller\CoreAbstractController;
use App\Enum\EventNameEnum;
use Doctrine\ORM\EntityManager;
use Framework\Http\AbstractController;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;

class CertificatesController extends CoreAbstractController
{
    protected CSRFValidator $CSRFValidator;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager)
    {
        $this->CSRFValidator = new CSRFValidator();
        parent::__construct($viewEngine, $entityManager);
    }

    protected function getKeyFingerprint(string $path): string
    {
        $key = file_get_contents($path);
        $key = str_replace(["-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----", "\n"], "", $key);
        $key = base64_decode($key);
        return strtoupper(sha1($key));
    }

    public function index(Request $request): Response
    {
        $user = $request->getAttributes()['user'];

        $privateKey = file_get_contents(__DIR__ . '/../../../storage/keys/master_private.pem');

        return $this->render("app/certs/index.twig", [
            'user' => $user,
            'public_key_pem' => file_get_contents(__DIR__ . '/../../../storage/keys/master_public.pem'),
            'private_key_pem' => $privateKey,
            'fingerprint_private' => $this->getKeyFingerprint(__DIR__ . '/../../../storage/keys/master_private.pem'),
            'fingerprint_public' => $this->getKeyFingerprint(__DIR__ . '/../../../storage/keys/master_public.pem'),
        ]);
    }

    public function rotate(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        if(!$this->CSRFValidator->handle($request)) {
            return $this->redirect("/app/certs");
        }

        $masterPrivateKey = openssl_pkey_new([
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($masterPrivateKey, $privateKey);
        $publicKey = openssl_pkey_get_details($masterPrivateKey)['key'];
        file_put_contents(__DIR__ . '/../../../storage/keys/master_private.pem', $privateKey);
        file_put_contents(__DIR__ . '/../../../storage/keys/master_public.pem', $publicKey);

        $this->auditLogService->log("system.certs.rotate.success", [
            'user_agent' => $request->getHeader('User-Agent'),
            'ip' => $request->getUserIp()
        ], "Master key pair rotated successfully", user: $user);

        $this->eventBusService->triggerEvent(EventNameEnum::SYSTEM_KEYS_ROTATE_SUCCESS, [
            'userAgent' => $request->getHeader('User-Agent'),
            'ip' => $request->getUserIp(),
            'author' => $user->getEmail()
        ]);

        return $this->redirect("/app/certs");
    }
}