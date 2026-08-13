<?php
declare(strict_types=1);

namespace App\Controller\API\v1;

use App\Controller\API\CoreApiController;
use App\Entity\Activation;
use App\Entity\LicenseKey;
use App\Enum\LicenseKeyStatusEnum;
use DateTime;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

class CertificateController extends CoreApiController
{
    protected const PUBLIC_KEY_PATH = __DIR__ . '/../../../../storage/keys/master_public.pem';
    protected const PRIVATE_KEY_PATH = __DIR__ . '/../../../../storage/keys/master_private.pem';

    protected const SUPPORTED_ALGORITHMS = [
        'sha256' => OPENSSL_ALGO_SHA256,
        'sha512' => OPENSSL_ALGO_SHA512,
    ];

    /**
     * @return Response
     */
    public function getPublicKey(): Response
    {
        $response = new Response();
        $response->setHeaders([
            'Content-Type' => 'application/x-pem-file',
            'Content-Disposition' => 'attachment; filename="master_public.pem"',
        ]);
        $response->setBody(file_get_contents(self::PUBLIC_KEY_PATH));

        return $response;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function signHash(Request $request): Response
    {
        $userIp = $request->getUserIp();
        $ipAllowList = explode(",", getenv("HASHING_IP_ALLOWLIST") ?: "");
        if(!in_array($userIp, $ipAllowList)) {
            return $this->error("IP_NOT_ALLOWED", "Your IP address is not allowed to access this endpoint.", 403, [
                'your_ip' => $userIp
            ]);
        }

        $licenseKey = $request->getBody("license_key");
        $activationId = $request->getBody("activation_id");
        $algorithm = $request->getBody("algorithm");
        $hash = $request->getBody("hash");

        if(!$licenseKey || !$algorithm || !$hash) {
            return $this->error("MISSING_PARAMETERS", "Missing required parameters.", 400, [
                'required_parameters' => ['license_key', 'algorithm', 'hash']
            ]);
        }

        if (!in_array($algorithm, array_keys(self::SUPPORTED_ALGORITHMS))) {
            return $this->error("UNSUPPORTED_ALGORITHM", "The specified algorithm is not supported.", 400, [
                'supported_algorithms' => self::SUPPORTED_ALGORITHMS,
                'specified_algorithm' => $algorithm
            ]);
        }

        $licenseRepository = $this->entityManager->getRepository(LicenseKey::class);
        $license = $licenseRepository->findOneBy([
            'key' => $licenseKey
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

        $activationRepository = $this->entityManager->getRepository(Activation::class);
        $activation = $activationRepository->findOneBy([
            'id' => $activationId
        ]);

        if(!$activation) {
            return $this->error("ACTIVATION_NOT_FOUND", "Activation not found.", 404);
        }

        if(!$activation->isActive()) {
            return $this->error("ACTIVATION_NOT_ACTIVE", "Activation not active.", 403);
        }

        $hashToSign = base64_decode($hash);
        $privateKey = openssl_pkey_get_private(file_get_contents(self::PRIVATE_KEY_PATH));
        openssl_sign($hashToSign, $signature, $privateKey, self::SUPPORTED_ALGORITHMS[$algorithm]);

        return $this->success([
            'signature' => base64_encode($signature),
            'signed_at' => (new DateTime())->getTimestamp(),
        ]);
    }
}