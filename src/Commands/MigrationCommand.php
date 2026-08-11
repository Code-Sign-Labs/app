<?php
declare(strict_types=1);
namespace App\Commands;

use Doctrine\ORM\EntityManager;
use Framework\Database\MigrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends Command
{
    protected MigrationManager $migrationManager;
    public function __construct(protected EntityManager $entityManager)
    {
        $this->migrationManager = new MigrationManager(
            $this->entityManager,
            $this->entityManager->getConnection()
        );
        parent::__construct();
    }

    public function getName(): ?string
    {
        return "migration";
    }

    public function getDescription(): string
    {
        return "Create a migration file for all entities in the database. The migration will include the structure and data of each entity's table.";
    }

    protected function configure()
    {
        $this->addArgument("type", null, "What do you want to do? (create, load)", null, ["create", "load"]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        switch ($type) {
            case "create":
                if ($this->migrationManager->createMigration()) {
                    $output->writeln("<info>Migration created successfully.</info>");
                    return Command::SUCCESS;
                } else {
                    $output->writeln("<error>Failed to create migration.</error>");
                    return Command::FAILURE;
                }
            case "load":
                $migrationFiles = glob(__DIR__ . '/../../../storage/migrations/*.sql');
                if (empty($migrationFiles)) {
                    $output->writeln("<comment>No migration files found.</comment>");
                    return Command::SUCCESS;
                }

                foreach ($migrationFiles as $file) {
                    $fileName = basename($file);
                    if ($this->migrationManager->loadMigration($fileName)) {
                        $output->writeln("<info>Migration '$fileName' loaded successfully.</info>");
                    } else {
                        $output->writeln("<error>Failed to load migration '$fileName'.</error>");
                        return Command::FAILURE;
                    }
                }
                return Command::SUCCESS;
        }
        $output->writeln("<error>Invalid type. Use 'create' or 'load'.</error>");
        return Command::FAILURE;
    }
}