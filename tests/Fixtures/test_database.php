<?php
declare(strict_types=1);
use Doctrine\ORM\EntityManager;
use Framework\Configs\ConfigCore;
use App\Entity\Customer;
use App\Entity\Activation;
use App\Entity\LicenseKey;
use App\Entity\Product;
use App\Enum\LicenseKeyStatusEnum;
use App\Enum\ProductStatusEnum;
use Doctrine\Common\Collections\ArrayCollection;

return [
    function (EntityManager $em, ConfigCore $configCore): void {
        $product = (new Product())
            ->setName('Integration Test Product')
            ->setSlug('integration-test-product')
            ->setVersion('1.0.0')
            ->setDescription('Product used only by the integration test suite.')
            ->setStatus(ProductStatusEnum::active);

        $customer = (new Customer())
            ->setLabel('Integration Test Customer')
            ->setEmail('integration@example.test')
            ->setAddress('Test Street 1')
            ->setCountry('PL')
            ->setPostalCode('00-001')
            ->setCity('Warsaw')
            ->setCurrency('PLN');

        $createLicense = static function (string $key, LicenseKeyStatusEnum $status, ?DateTime $expiresAt = null, int $maxActivations = 2, int $currentActivations = 0) use ($product, $customer): LicenseKey {
            return (new LicenseKey())
                ->setProduct($product)
                ->setCustomer($customer)
                ->setKey($key)
                ->setStatus($status)
                ->setExpiresAt($expiresAt)
                ->setMaxActivations($maxActivations)
                ->setCurrentActivations($currentActivations)
                ->setActivations(new ArrayCollection());
        };

        $em->persist($product);
        $em->persist($customer);
        $em->persist($createLicense('integration-active-key', LicenseKeyStatusEnum::active));
        $em->persist($createLicense('integration-suspended-key', LicenseKeyStatusEnum::suspended));
        $em->persist($createLicense('integration-expired-key', LicenseKeyStatusEnum::active, new DateTime('-1 day')));
        $em->persist($createLicense('integration-full-key', LicenseKeyStatusEnum::active, null, 1, 1));
        $em->persist($createLicense('integration-capacity-key', LicenseKeyStatusEnum::active, null, 2));
        $em->persist($createLicense('integration-capacity-limit-key', LicenseKeyStatusEnum::active, null, 2));
        $em->persist($createLicense('integration-metadata-key', LicenseKeyStatusEnum::active));

        $signingLicense = $createLicense('integration-signing-key', LicenseKeyStatusEnum::active, null, 2, 1);
        $activeActivation = (new Activation())
            ->setLicenseKey($signingLicense)
            ->setDeviceName('Signing laptop')
            ->setDeviceIdentifier('signing-device')
            ->setIpAddress('127.0.0.1')
            ->setActive(true);
        $inactiveActivation = (new Activation())
            ->setLicenseKey($signingLicense)
            ->setDeviceName('Retired laptop')
            ->setDeviceIdentifier('retired-device')
            ->setIpAddress('127.0.0.1')
            ->setActive(false)
            ->setDeactivatedAt(new DateTime('-1 hour'));

        $em->persist($signingLicense);
        $em->persist($activeActivation);
        $em->persist($inactiveActivation);
    }
];
