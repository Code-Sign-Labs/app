<?php

namespace App\Service;

use App\Entity\Config;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class ConfigService
{
    protected EntityRepository $configRepository;
    protected Config $config;
    public function __construct(protected EntityManager $entityManager)
    {
        $this->configRepository = $this->entityManager->getRepository(Config::class);

        $config = $this->configRepository->findAll();
        if(!isset($config[0])) {
            $config = $this->getDefaultConfig();
            $this->entityManager->persist($config);
            $this->entityManager->flush();
            $this->config = $config;
            return;
        }
        $this->config = $config[0];
    }

    protected function getDefaultConfig(): Config
    {
        $config = new Config();

        $config->setBaseUrl("http://localhost")
            ->setInstanceName("CodeSign")
            ->setKeyPrefix("CS")
            ->setKeyPattern("XXX-X-XX-XXXXX-X-XXXXX-XXX-XXXXXXXX-XXX-XX-X")
            ->setMinPasswordLength(8)
            ->setPasswordRequiresNumbers(true)
            ->setPasswordRequiresUppercase(true)
            ->setPasswordRequiresSpecial(true);

        return $config;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}