<?php
declare(strict_types=1);
namespace App\Commands;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

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
        return "Initialize Code Sign app";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Symfony style
        $style = new SymfonyStyle($input, $output);
        $style->info("Initialize Code Sign App");
        // Create admin account
        $admin = new User();
        $admin->setName("admin")
            ->setPassword(password_hash("admin", PASSWORD_DEFAULT))
            ->setEmail("admin@domain.com");

        try {
            $this->entityManager->persist($admin);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $style->warning("ERROR: " . $exception->getMessage());
        }

        $style->success("Admin account created with username 'admin' and password 'admin'");
        $style->caution("Please change the password after first login!");

        $style->info("Initializing pair master of keys for Code Sign App");
        $masterPrivateKey = openssl_pkey_new([
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($masterPrivateKey, $privateKey);
        $publicKey = openssl_pkey_get_details($masterPrivateKey)['key'];
        $style->info("Master private key generated and stored in storage/keys/master_private.pem");
        file_put_contents(__DIR__ . '/../../storage/keys/master_private.pem', $privateKey);
        $style->info("Master public key generated and stored in storage/keys/master_public.pem");
        file_put_contents(__DIR__ . '/../../storage/keys/master_public.pem', $publicKey);

        $style->success("Initialization completed successfully.");

        return Command::SUCCESS;
    }
}