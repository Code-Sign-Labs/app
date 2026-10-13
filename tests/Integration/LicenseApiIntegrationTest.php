<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Entity\Activation;
use App\Entity\LicenseKey;
use Doctrine\ORM\EntityManager;
use Tests\Core\RouterTestClient;
use Tests\Core\Test;

class LicenseApiIntegrationTest extends Test
{
    private RouterTestClient $client;
    private EntityManager $entityManager;

    public function setUp(): void
    {
        $this->client = $this->getRouter();
        $this->entityManager = $this->container->resolve(EntityManager::class);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SESSION = [];
    }

    public function testRedirectsUnauthenticatedUsersFromProtectedApplicationPages(): void
    {
        foreach (['/app/', '/app/products', '/app/licenses', '/app/users', '/app/settings'] as $uri) {
            $response = $this->client->request('GET', $uri);

            $this->assertHttpStatus(302, $response);
            $this->assertSame('/login', $response->getHeader('Location'));
        }
    }

    public function testReturnsAJsonNotFoundResponseForUnknownApiRoute(): void
    {
        $response = $this->client->request('GET', '/api/v1/unknown');

        $this->assertHttpStatus(404, $response);
        $this->assertContainsError($response, 'NOT_FOUND', 'The requested resource was not found');
    }

    public function testValidatesAnActiveLicenseAndReturnsProductData(): void
    {
        $response = $this->client->request('GET', '/api/v1/licenses/integration-active-key', [
            'User-Agent' => 'integration-test',
        ]);

        $this->assertSuccessResponse($response);
        $body = $this->decodeJsonResponse($response);
        $this->assertSame('Integration Test Product', $body['response']['product']['name']);
        $this->assertSame(2, $body['response']['max_activations']);
        $this->assertSame(0, $body['response']['current_activations']);
    }

    public function testRejectsUnknownInactiveAndExpiredLicenses(): void
    {
        $unknown = $this->client->request('GET', '/api/v1/licenses/missing-key');
        $suspended = $this->client->request('GET', '/api/v1/licenses/integration-suspended-key');
        $expired = $this->client->request('GET', '/api/v1/licenses/integration-expired-key');

        $this->assertHttpStatus(404, $unknown);
        $this->assertContainsError($unknown, 'LICENSE_NOT_FOUND');
        $this->assertHttpStatus(403, $suspended);
        $this->assertContainsError($suspended, 'LICENSE_INACTIVE');
        $this->assertHttpStatus(403, $expired);
        $this->assertContainsError($expired, 'LICENSE_EXPIRED');
    }

    public function testRejectsActivationWhenRequestDataIsMissingOrLimitIsReached(): void
    {
        $missingData = $this->client->request('POST', '/api/v1/licenses/integration-active-key/activate');
        $atLimit = $this->client->request('POST', '/api/v1/licenses/integration-full-key/activate', body: [
            'device_name' => 'Laptop',
            'device_identifier' => 'device-at-limit',
        ]);

        $this->assertHttpStatus(400, $missingData);
        $this->assertContainsError($missingData, 'DEVICE_INFO_MISSING');
        $this->assertHttpStatus(403, $atLimit);
        $this->assertContainsError($atLimit, 'LICENSE_MAX_ACTIVATIONS_REACHED');
    }

    public function testRejectsActivationForUnknownInactiveAndExpiredLicenses(): void
    {
        $body = ['device_name' => 'Laptop', 'device_identifier' => 'device-1'];

        $unknown = $this->client->request('POST', '/api/v1/licenses/missing-key/activate', body: $body);
        $inactive = $this->client->request('POST', '/api/v1/licenses/integration-suspended-key/activate', body: $body);
        $expired = $this->client->request('POST', '/api/v1/licenses/integration-expired-key/activate', body: $body);

        $this->assertHttpStatus(404, $unknown);
        $this->assertContainsError($unknown, 'LICENSE_NOT_FOUND');
        $this->assertHttpStatus(403, $inactive);
        $this->assertContainsError($inactive, 'LICENSE_INACTIVE');
        $this->assertHttpStatus(403, $expired);
        $this->assertContainsError($expired, 'LICENSE_EXPIRED');
    }

    public function testRejectsDeviceListingForUnknownInactiveAndExpiredLicenses(): void
    {
        $unknown = $this->client->request('GET', '/api/v1/licenses/missing-key/devices');
        $inactive = $this->client->request('GET', '/api/v1/licenses/integration-suspended-key/devices');
        $expired = $this->client->request('GET', '/api/v1/licenses/integration-expired-key/devices');

        $this->assertHttpStatus(404, $unknown);
        $this->assertContainsError($unknown, 'LICENSE_NOT_FOUND');
        $this->assertHttpStatus(403, $inactive);
        $this->assertContainsError($inactive, 'LICENSE_INACTIVE');
        $this->assertHttpStatus(403, $expired);
        $this->assertContainsError($expired, 'LICENSE_EXPIRED');
    }

    public function testRejectsMissingUnknownForeignAndInactiveDeactivationRequests(): void
    {
        $signingActivation = $this->entityManager->getRepository(Activation::class)->findOneBy(['deviceIdentifier' => 'signing-device']);
        $inactiveActivation = $this->entityManager->getRepository(Activation::class)->findOneBy(['deviceIdentifier' => 'retired-device']);

        $missing = $this->client->request('POST', '/api/v1/licenses/integration-active-key/deactivate');
        $unknown = $this->client->request('POST', '/api/v1/licenses/integration-active-key/deactivate', body: ['activation_id' => '00000000-0000-0000-0000-000000000000']);
        $foreign = $this->client->request('POST', '/api/v1/licenses/integration-active-key/deactivate', body: ['activation_id' => $signingActivation->getId()]);
        $inactive = $this->client->request('POST', '/api/v1/licenses/integration-signing-key/deactivate', body: ['activation_id' => $inactiveActivation->getId()]);

        $this->assertHttpStatus(400, $missing);
        $this->assertContainsError($missing, 'ACTIVATION_ID_MISSING');
        $this->assertHttpStatus(404, $unknown);
        $this->assertContainsError($unknown, 'ACTIVATION_NOT_FOUND');
        $this->assertHttpStatus(404, $foreign);
        $this->assertContainsError($foreign, 'ACTIVATION_NOT_FOUND');
        $this->assertHttpStatus(403, $inactive);
        $this->assertContainsError($inactive, 'ACTIVATION_NOT_ACTIVE');
    }

    public function testReturnsThePublicSigningKey(): void
    {
        $response = $this->client->request('GET', '/api/v1/public-key');

        $this->assertHttpStatus(200, $response);
        $this->assertSame('application/x-pem-file', $response->getHeader('Content-Type'));
        $this->assertTrue(str_contains($response->getBody(), 'BEGIN PUBLIC KEY'));
    }

    public function testRejectsSigningWhenIpOrParametersAreInvalid(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        $blocked = $this->client->request('POST', '/api/v1/sign');

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $missing = $this->client->request('POST', '/api/v1/sign');
        $unsupported = $this->client->request('POST', '/api/v1/sign', body: [
            'license_key' => 'integration-signing-key',
            'activation_id' => 'unused',
            'algorithm' => 'md5',
            'hash' => base64_encode('payload'),
        ]);

        $this->assertHttpStatus(403, $blocked);
        $this->assertContainsError($blocked, 'IP_NOT_ALLOWED');
        $this->assertHttpStatus(400, $missing);
        $this->assertContainsError($missing, 'MISSING_PARAMETERS');
        $this->assertHttpStatus(400, $unsupported);
        $this->assertContainsError($unsupported, 'UNSUPPORTED_ALGORITHM');
    }

    public function testSignsPayloadForAnActiveLicenseAndActivation(): void
    {
        $activation = $this->entityManager->getRepository(Activation::class)->findOneBy(['deviceIdentifier' => 'signing-device']);
        $payload = 'payload to sign';
        $response = $this->client->request('POST', '/api/v1/sign', body: [
            'license_key' => 'integration-signing-key',
            'activation_id' => $activation->getId(),
            'algorithm' => 'sha256',
            'hash' => base64_encode($payload),
        ]);

        $this->assertSuccessResponse($response);
        $signature = $this->decodeJsonResponse($response)['response']['signature'];
        $publicKey = file_get_contents(__DIR__ . '/../../storage/keys/master_public.pem');
        $this->assertSame(1, openssl_verify($payload, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256));
    }

    public function testRejectsSigningForMissingLicenseAndInactiveActivation(): void
    {
        $activeActivation = $this->entityManager->getRepository(Activation::class)->findOneBy(['deviceIdentifier' => 'signing-device']);
        $inactiveActivation = $this->entityManager->getRepository(Activation::class)->findOneBy(['deviceIdentifier' => 'retired-device']);
        $request = [
            'algorithm' => 'sha512',
            'hash' => base64_encode('payload'),
        ];

        $unknownLicense = $this->client->request('POST', '/api/v1/sign', body: $request + [
            'license_key' => 'missing-key',
            'activation_id' => $activeActivation->getId(),
        ]);
        $inactiveActivationResponse = $this->client->request('POST', '/api/v1/sign', body: $request + [
            'license_key' => 'integration-signing-key',
            'activation_id' => $inactiveActivation->getId(),
        ]);

        $this->assertHttpStatus(404, $unknownLicense);
        $this->assertContainsError($unknownLicense, 'LICENSE_NOT_FOUND');
        $this->assertHttpStatus(403, $inactiveActivationResponse);
        $this->assertContainsError($inactiveActivationResponse, 'ACTIVATION_NOT_ACTIVE');
    }

    public function testActivatesListsAndDeactivatesADevice(): void
    {
        $activationResponse = $this->client->request('POST', '/api/v1/licenses/integration-active-key/activate', [
            'User-Agent' => 'integration-test',
        ], body: [
            'device_name' => 'Integration Laptop',
            'device_identifier' => 'device-integration-1',
        ]);

        $this->assertSuccessResponse($activationResponse);
        $activationBody = $this->decodeJsonResponse($activationResponse);
        $activationId = $activationBody['response']['activation']['id'];
        $this->assertSame('device-integration-1', $activationBody['response']['activation']['device_identifier']);

        $devicesResponse = $this->client->request('GET', '/api/v1/licenses/integration-active-key/devices');
        $this->assertSuccessResponse($devicesResponse);
        $devicesBody = $this->decodeJsonResponse($devicesResponse);
        $this->assertSame($activationId, $devicesBody['response']['activations'][0]['id']);
        $this->assertTrue($devicesBody['response']['activations'][0]['active']);

        $deactivationResponse = $this->client->request('POST', '/api/v1/licenses/integration-active-key/deactivate', body: [
            'activation_id' => $activationId,
        ]);
        $this->assertSuccessResponse($deactivationResponse);
        $this->assertFalse($this->decodeJsonResponse($deactivationResponse)['response']['activation']['active']);

        $this->entityManager->clear();
        $license = $this->entityManager->getRepository(LicenseKey::class)->findOneBy(['key' => 'integration-active-key']);
        $activation = $this->entityManager->getRepository(Activation::class)->find($activationId);
        $this->assertSame(0, $license->getCurrentActivations());
        $this->assertFalse($activation->isActive());
        $this->assertNotNull($activation->getDeactivatedAt());
    }
}
