<?php
declare(strict_types=1);

namespace App\Cron;

use Doctrine\ORM\EntityManager;
use Framework\Messenger\MessageDispatcher;

class DispatchCron
{
    public function __construct(protected EntityManager $entityManager)
    {
    }

    public function run(): void
    {
        try {
            (
                new MessageDispatcher($this->entityManager)
            )->callAllMessages();

            echo "All messages have been dispatched successfully.\n";

        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }
    }
}