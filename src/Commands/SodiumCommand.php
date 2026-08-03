<?php

namespace App\Commands;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SodiumCommand extends Command
{
    public function getName(): ?string
    {
        return 'app:utils:sodium';
    }

    public function getDescription(): string
    {
        return 'Sodium function utilities';
    }

    public function __construct(protected EntityManager $entityManager)
    {
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $option = $style->choice('Select action', [
            'version',
            'keygen',
        ]);

        if($option == "version") {
            $version = phpversion("sodium");
            $style->writeln("Sodium library version: " . $version);
            return Command::SUCCESS;
        } elseif($option == "keygen") {
            $key = sodium_crypto_sign_keypair();
            $privateKey = sodium_crypto_sign_secretkey($key);
            echo base64_encode($privateKey) . PHP_EOL;
            return Command::SUCCESS;
        } else {
            $style->error("Invalid option selected.");
            return Command::FAILURE;
        }
    }
}