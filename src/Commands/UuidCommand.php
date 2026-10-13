<?php
declare(strict_types=1);
namespace App\Commands;

use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UuidCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getName(): ?string
    {
        return 'app:utils:uuid';
    }

    public function getDescription(): string
    {
        return 'Generate a UUID v4.';
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $uuid = Uuid::uuid4()->toString();
        $output->writeln($uuid);
        return Command::SUCCESS;
    }
}