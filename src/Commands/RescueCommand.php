<?php
declare(strict_types=1);

namespace App\Commands;

use App\Controller\App\SettingsController;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RescueCommand extends Command
{
    public function getName(): ?string
    {
        return "rescue:ip-allowlist";
    }

    public function getDescription(): string
    {
        return "Rescue access to the app by disabling IP allowlist";
    }

    public function __construct(protected EntityManager $entityManager, ?string $name = null, ?callable $code = null)
    {
        parent::__construct($name, $code);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->info("Rescuing access to the app by disabling IP allowlist");
        $envPath = __DIR__ . '/../../.env';
        if (!file_exists($envPath)) {
            $style->error(".env file not found");
            return Command::FAILURE;
        }

        SettingsController::updateEnv($envPath, "ACCESS_IP_ALLOWLIST_ENABLED", "false");
        $style->success("IP allowlist disabled. You should now be able to access the app.");
        return Command::SUCCESS;
    }
}