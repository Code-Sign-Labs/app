<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Entity\Activation;
use App\Entity\LicenseKey;
use Doctrine\ORM\EntityManager;
use Tests\Core\RouterTestClient;
use Tests\Core\Test;

class PublicApiContractIntegrationTest extends Test
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

    public function testActiveLicenseResponseUsesJsonContentType(): void
    {
        $response = $this->license('integration-active-key');
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }

    public function testActiveLicenseResponseContainsNoBatchWhenNoneIsAssigned(): void
    {
        $body = $this->body($this->license('integration-active-key'));
        $this->assertNull($body['response']['batch']);
    }

    public function testActiveLicenseResponseContainsNoExpiryForPerpetualLicense(): void
    {
        $body = $this->body($this->license('integration-active-key'));
        $this->assertNull($body['response']['expires_at']);
    }

    public function testActiveLicenseResponseContainsCreatedTimestamp(): void
    {
        $body = $this->body($this->license('integration-active-key'));
        $this->assertTrue(is_int($body['response']['created_at']));
    }

    public function testActiveLicenseResponseSignatureIsValid(): void
    {
        $body = $this->body($this->license('integration-active-key'));
        $payload = json_encode($body['response'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $publicKey = file_get_contents(__DIR__ . '/../../storage/keys/master_public.pem');

        $this->assertSame(1, openssl_verify($payload, base64_decode($body['signature']), $publicKey, OPENSSL_ALGO_SHA256));
    }

    public function testMissingLicenseReturnsJsonContentType(): void
    {
        $response = $this->license('not-present');
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }

    public function testMissingLicenseDoesNotExposeResponseData(): void
    {
        $body = $this->body($this->license('not-present'));
        $this->assertSame([], $body['response']);
    }

    public function testInactiveLicenseDoesNotExposeResponseData(): void
    {
        $body = $this->body($this->license('integration-suspended-key'));
        $this->assertSame([], $body['response']);
    }

    public function testExpiredLicenseDoesNotExposeResponseData(): void
    {
        $body = $this->body($this->license('integration-expired-key'));
        $this->assertSame([], $body['response']);
    }

    public function testLicenseErrorResponseSignatureIsValid(): void
    {
        $body = $this->body($this->license('not-present'));
        $payload = json_encode($body['error'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $publicKey = file_get_contents(__DIR__ . '/../../storage/keys/master_public.pem');

        $this->assertSame(1, openssl_verify($payload, base64_decode($body['signature']), $publicKey, OPENSSL_ALGO_SHA256));
    }

    public function testSigningSupportsSha512(): void
    {
        $activation = $this->activation('signing-device');
        $payload = 'sha512 payload';
        $response = $this->client->request('POST', '/api/v1/sign', body: [
            'license_key' => 'integration-signing-key',
            'activation_id' => $activation->getId(),
            'algorithm' => 'sha512',
            'hash' => base64_encode($payload),
        ]);
        $body = $this->body($response);
        $publicKey = file_get_contents(__DIR__ . '/../../storage/keys/master_public.pem');

        $this->assertSuccessResponse($response);
        $this->assertSame(1, openssl_verify($payload, base64_decode($body['response']['signature']), $publicKey, OPENSSL_ALGO_SHA512));
    }

    public function testSigningResponseContainsTimestamp(): void
    {
        $activation = $this->activation('signing-device');
        $response = $this->client->request('POST', '/api/v1/sign', body: [
            'license_key' => 'integration-signing-key',
            'activation_id' => $activation->getId(),
            'algorithm' => 'sha256',
            'hash' => base64_encode('timestamp payload'),
        ]);

        $this->assertTrue(is_int($this->body($response)['response']['signed_at']));
    }

    public function testSigningRejectsUnknownActivation(): void
    {
        $response = $this->client->request('POST', '/api/v1/sign', body: [
            'license_key' => 'integration-signing-key',
            'activation_id' => '00000000-0000-0000-0000-000000000000',
            'algorithm' => 'sha256',
            'hash' => base64_encode('payload'),
        ]);

        $this->assertHttpStatus(404, $response);
        $this->assertContainsError($response, 'ACTIVATION_NOT_FOUND');
    }

    public function testSigningRejectsInactiveLicense(): void
    {
        $activation = $this->activation('signing-device');
        $response = $this->client->request('POST', '/api/v1/sign', body: [
            'license_key' => 'integration-suspended-key',
            'activation_id' => $activation->getId(),
            'algorithm' => 'sha256',
            'hash' => base64_encode('payload'),
        ]);

        $this->assertHttpStatus(403, $response);
        $this->assertContainsError($response, 'LICENSE_INACTIVE');
    }

    public function testPublicKeyHasAttachmentDisposition(): void
    {
        $response = $this->client->request('GET', '/api/v1/public-key');
        $this->assertSame('attachment; filename="master_public.pem"', $response->getHeader('Content-Disposition'));
    }

    public function testPublicKeyMatchesStoredKey(): void
    {
        $response = $this->client->request('GET', '/api/v1/public-key');
        $this->assertSame(file_get_contents(__DIR__ . '/../../storage/keys/master_public.pem'), $response->getBody());
    }

    public function testPostToPublicKeyRouteIsNotFound(): void
    {
        $response = $this->client->request('POST', '/api/v1/public-key');
        $this->assertHttpStatus(404, $response);
        $this->assertContainsError($response, 'NOT_FOUND');
    }

    public function testGetToActivationRouteIsNotFound(): void
    {
        $response = $this->client->request('GET', '/api/v1/licenses/integration-active-key/activate');
        $this->assertHttpStatus(404, $response);
        $this->assertContainsError($response, 'NOT_FOUND');
    }

    public function testUnknownPostApiRouteReturnsJsonNotFound(): void
    {
        $response = $this->client->request('POST', '/api/v9/unknown');
        $this->assertHttpStatus(404, $response);
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }

    public function testDeviceListReturnsActiveAndInactiveActivations(): void
    {
        $response = $this->client->request('GET', '/api/v1/licenses/integration-signing-key/devices');
        $activations = $this->body($response)['response']['activations'];

        $this->assertSame(2, count($activations));
    }

    public function testDeviceListPreservesActiveActivationFlag(): void
    {
        $activations = $this->body($this->client->request('GET', '/api/v1/licenses/integration-signing-key/devices'))['response']['activations'];
        $active = array_values(array_filter($activations, static fn (array $activation): bool => $activation['device_identifier'] === 'signing-device'))[0];

        $this->assertTrue($active['active']);
    }

    public function testDeviceListPreservesInactiveActivationFlag(): void
    {
        $activations = $this->body($this->client->request('GET', '/api/v1/licenses/integration-signing-key/devices'))['response']['activations'];
        $inactive = array_values(array_filter($activations, static fn (array $activation): bool => $activation['device_identifier'] === 'retired-device'))[0];

        $this->assertFalse($inactive['active']);
    }

    public function testForeignDeactivationDoesNotChangeLicenseActivationCount(): void
    {
        $license = $this->licenseEntity('integration-active-key');
        $before = $license->getCurrentActivations();
        $response = $this->client->request('POST', '/api/v1/licenses/integration-active-key/deactivate', body: [
            'activation_id' => $this->activation('signing-device')->getId(),
        ]);
        $this->entityManager->clear();

        $this->assertHttpStatus(404, $response);
        $this->assertSame($before, $this->licenseEntity('integration-active-key')->getCurrentActivations());
    }

    public function testInactiveDeactivationDoesNotChangeLicenseActivationCount(): void
    {
        $license = $this->licenseEntity('integration-signing-key');
        $before = $license->getCurrentActivations();
        $response = $this->client->request('POST', '/api/v1/licenses/integration-signing-key/deactivate', body: [
            'activation_id' => $this->activation('retired-device')->getId(),
        ]);
        $this->entityManager->clear();

        $this->assertHttpStatus(403, $response);
        $this->assertSame($before, $this->licenseEntity('integration-signing-key')->getCurrentActivations());
    }

    public function testActivationRequiresDeviceName(): void
    {
        $response = $this->client->request('POST', '/api/v1/licenses/integration-capacity-key/activate', body: [
            'device_identifier' => 'identifier-only',
        ]);
        $this->assertHttpStatus(400, $response);
        $this->assertContainsError($response, 'DEVICE_INFO_MISSING');
    }

    public function testActivationRequiresDeviceIdentifier(): void
    {
        $response = $this->client->request('POST', '/api/v1/licenses/integration-capacity-key/activate', body: [
            'device_name' => 'name-only',
        ]);
        $this->assertHttpStatus(400, $response);
        $this->assertContainsError($response, 'DEVICE_INFO_MISSING');
    }

    public function testLicenseAllowsActivationsUpToConfiguredCapacity(): void
    {
        foreach (['first-device', 'second-device'] as $identifier) {
            $response = $this->client->request('POST', '/api/v1/licenses/integration-capacity-key/activate', body: [
                'device_name' => 'Capacity device',
                'device_identifier' => $identifier,
            ]);
            $this->assertSuccessResponse($response);
        }

        $this->entityManager->clear();
        $this->assertSame(2, $this->licenseEntity('integration-capacity-key')->getCurrentActivations());
    }

    public function testLicenseRejectsActivationBeyondConfiguredCapacity(): void
    {
        foreach (['limit-first-device', 'limit-second-device'] as $identifier) {
            $allowed = $this->client->request('POST', '/api/v1/licenses/integration-capacity-limit-key/activate', body: [
                'device_name' => 'Capacity device',
                'device_identifier' => $identifier,
            ]);
            $this->assertSuccessResponse($allowed);
        }

        $response = $this->client->request('POST', '/api/v1/licenses/integration-capacity-limit-key/activate', body: [
            'device_name' => 'Over capacity device',
            'device_identifier' => 'third-device',
        ]);
        $this->assertHttpStatus(403, $response);
        $this->assertContainsError($response, 'LICENSE_MAX_ACTIVATIONS_REACHED');
    }

    public function testSuccessfulActivationReturnsRequestedDeviceMetadata(): void
    {
        $response = $this->client->request('POST', '/api/v1/licenses/integration-metadata-key/activate', body: [
            'device_name' => 'Metadata device',
            'device_identifier' => 'metadata-device',
        ]);
        $this->assertSuccessResponse($response);
        $activation = $this->body($response)['response']['activation'];
        $this->assertSame('Metadata device', $activation['device_name']);
        $this->assertSame('metadata-device', $activation['device_identifier']);
        $this->assertSame('127.0.0.1', $activation['ip']);
    }

    private function license(string $key): \Framework\Http\Objects\Response
    {
        return $this->client->request('GET', '/api/v1/licenses/' . $key);
    }

    private function body(\Framework\Http\Objects\Response $response): array
    {
        return $this->decodeJsonResponse($response);
    }

    private function activation(string $identifier): Activation
    {
        return $this->entityManager->getRepository(Activation::class)->findOneBy(['deviceIdentifier' => $identifier]);
    }

    private function licenseEntity(string $key): LicenseKey
    {
        return $this->entityManager->getRepository(LicenseKey::class)->findOneBy(['key' => $key]);
    }
}
