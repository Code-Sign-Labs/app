<?php

namespace App\Commands;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends Command
{
    public function __construct(protected EntityManager $entityManager)
    {
        parent::__construct();
    }

    public function getName(): ?string
    {
        return "init";
    }

    public function getDescription(): string
    {
        return "Initialize Code Sign controller";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Symfony style
        $style = new SymfonyStyle($input, $output);
        $style->info("Initialize Code Sign controller");
        // Create admin account
        $admin = new User();
        $admin->setName("admin")
            ->setPassword(password_hash("admin", PASSWORD_DEFAULT))
            ->setEmail("admin@domain.com");

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $style->success("Admin account created with username 'admin' and password 'admin'");
        $style->caution("Please change the password after first login!");

        return Command::SUCCESS;
    }
}