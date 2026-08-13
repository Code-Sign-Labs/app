<?php
declare(strict_types=1);
namespace App\Commands;

use App\Entity\Admin;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronCommand extends Command
{
    public function getName(): ?string
    {
        return 'app:cron';
    }

    public function getDescription(): string
    {
        return 'Run scheduled cron jobs.';
    }

    public function __construct(protected EntityManager $entityManager, ?string $name = null, ?callable $code = null)
    {
        parent::__construct($name, $code);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $cronPath = __DIR__ . '/../../src/Cron/';
        $cronJobs = glob($cronPath . '*.php');
        foreach ($cronJobs as $cronJobFile) {
            $cronJobClass = 'App\\Cron\\' . basename($cronJobFile, '.php');
            if (class_exists($cronJobClass)) {
                $cronJobInstance = new $cronJobClass($this->entityManager);
                if (method_exists($cronJobInstance, 'run')) {
                    $cronJobInstance->run();
                }
            }
        }
        $style->success('Cron jobs executed successfully.');
        return Command::SUCCESS;
    }
}