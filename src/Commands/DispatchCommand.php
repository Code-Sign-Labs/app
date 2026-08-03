<?php

namespace App\Commands;

use Doctrine\ORM\EntityManager;
use Framework\Messenger\MessageDispatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DispatchCommand extends Command
{
    public function getName(): ?string
    {
        return 'app:dispatch';
    }

    public function getDescription(): string
    {
        return 'Dispatch all messages.';
    }

    public function __construct(protected EntityManager $entityManager)
    {
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        try {
            $messageDispatcher = new MessageDispatcher($this->entityManager);
            $messageDispatcher->callAllMessages();
            $style->success('All messages have been dispatched successfully.');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
            $style->error('An error occurred while dispatching messages.');
            return Command::FAILURE;
        }
    }
}