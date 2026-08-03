<?php

namespace Tests\Core;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Framework\Configs\ConfigCore;
use Framework\Utils\DoctrineUuidType;
use Doctrine\DBAL\Types\Type;

class TestDatabase
{
    public function __construct(protected EntityManager $entityManager, protected ConfigCore $configCore)
    {
        if (!Type::hasType(DoctrineUuidType::NAME)) {
            Type::addType(DoctrineUuidType::NAME, DoctrineUuidType::class);
        }
    }

    public function rebuild(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (empty($metadata)) {
            return;
        }

        $schemaTool = new SchemaTool($this->entityManager);
        $conn = $this->entityManager->getConnection();
        $params = $conn->getParams();

        try {
            // Attempt a normal drop
            $schemaTool->dropSchema($metadata);
        } catch (\Throwable $e) {

            if (isset($params['driver']) && $params['driver'] === 'pdo_sqlite') {

                $path = $params['path'] ?? ($params['dbname'] ?? null);
                if (!empty($path) && $path !== ':memory:' && file_exists($path)) {

                    try {
                        $conn->close();
                    } catch (\Throwable) {
                        // ignore
                    }
                    @unlink($path);
                }
            }

            try {
                $platform = $conn->getDatabasePlatform();
                foreach ($metadata as $meta) {
                    $table = $meta->getTableName();
                    $sql = 'DROP TABLE IF EXISTS ' . $platform->quoteIdentifier($table);
                    try {
                        $conn->executeStatement($sql);
                    } catch (\Throwable) {
                        // ignore individual failures
                    }
                }
            } catch (\Throwable) {
                // ignore, we'll attempt createSchema below which may still fail and bubble up
            }
        }

        $schemaTool->createSchema($metadata);
    }

    public function loadFixtureFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $fixture = require $filePath;
        $fixtures = is_array($fixture) ? $fixture : [$fixture];

        foreach ($fixtures as $callable) {
            if (is_callable($callable)) {
                $callable($this->entityManager, $this->configCore);
            }
        }

        $this->entityManager->flush();
    }
}

