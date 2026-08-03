<?php

namespace Framework\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;

class MigrationManager
{
    public function __construct(protected EntityManager $entityManager, protected Connection $connection)
    {
        if(!is_dir(__DIR__ . '/../../storage/migrations')) {
            mkdir(__DIR__ . '/../../storage/migrations', 0755, true);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function doesMigrationExists(string $name): bool
    {
        $filePath = __DIR__ . '/../../../storage/migrations/' . $name;
        return file_exists($filePath);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function loadMigration(string $name): bool
    {
        $filePath = __DIR__ . '/../../storage/migrations/' . $name;

        if(!file_exists($filePath)) {
            return false;
        }

        try {
            $sql = file_get_contents($filePath);
            $this->connection->executeStatement($sql);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create a migration file for all entities in the database. The migration will include the structure and data of each entity's table.
     *
     * @return bool
     */
    public function createMigration(): bool
    {
        try {
            $dump = $this->createMigrationForAllEntities();

            $fileName = 'migration_' . date('Ymd_His') . '.sql';
            $filePath = __DIR__ . '/../../storage/migrations/' . $fileName;

            if($dump === '') {
                return false;
            }

            file_put_contents($filePath, $dump);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * @return string
     * @throws Exception
     */
    protected function createMigrationForAllEntities(): string
    {
        $dump = [];

        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach($metaData as $meta) {
            $dump[] = $this->createMigrationForEntity($meta->getName());
        }

        return implode("\n", $dump);
    }

    /**
     * @param string $entityClass
     * @return string
     * @throws Exception
     */
    protected function createMigrationForEntity(string $entityClass): string
    {
        $meta = $this->entityManager->getClassMetadata($entityClass);
        $table = $meta->getTableName();

        $dump = [];

        $create = $this->connection->fetchAssociative("SHOW CREATE TABLE `{$table}`");

        if(!$create || !isset($create['Create Table'])) {
            throw new \RuntimeException("Cannot read structure of table '{$table}'");
        }

        $dump[] = "-- Backup of entity table `{$table}`";
        $dump[] = "DROP TABLE IF EXISTS `{$table}`;";
        $dump[] = $create['Create Table'] . ';';
        $dump[] = "";

        $rows = $this->connection->fetchAllAssociative("SELECT * FROM `{$table}`");

        if(!empty($rows)) {
            foreach($rows as $row) {
                $cols = array_map(fn($c) => "`{$c}`", array_keys($row));
                $vals = array_map(
                    fn($v) => $v === null ? 'NULL' : $this->connection->quote($v),
                    array_values($row)
                );

                $dump[] = sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);",
                    $table,
                    implode(', ', $cols),
                    implode(', ', $vals)
                );
            }
        } else {
            $dump[] = "-- No data to backup for table `{$table}`";
        }

        return implode("\n", $dump);
    }
}