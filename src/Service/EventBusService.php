<?php
declare(strict_types=1);
namespace App\Service;

use App\Entity\Webhook;
use App\Entity\WebhookDelivery;
use App\Enum\EventNameEnum;
use App\Enum\WebhookDeliveryStatusEnum;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Throwable;

class EventBusService
{
    protected EntityRepository $webhookRepository;
    protected WebhookFactory $webhookFactory;
    public function __construct(protected EntityManager $entityManager)
    {
        $this->webhookFactory = new WebhookFactory();
        $this->webhookRepository = $this->entityManager->getRepository(Webhook::class);
    }

    /**
     * @param EventNameEnum $event
     * @param array $data
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function triggerEvent(EventNameEnum $event, array $data): void
    {
        // events json needs to contain our string $event
        $webhooks = $this->webhookRepository->createQueryBuilder('w')
            ->where('JSON_CONTAINS(w.events, :event) = 1')
            ->setParameter('event', json_encode($event->value))
            ->getQuery()
            ->getResult();



        /**
         * @var Webhook $webhook
         */
        foreach ($webhooks as $webhook) {
            $webhookDelivery = new WebhookDelivery();
            $webhookDelivery->setWebhook($webhook)
                ->setStatus(WebhookDeliveryStatusEnum::await_delivery)
                ->setRequestHeaders($webhook->getHeaders())
                ->setRequestBody($this->webhookFactory->create($webhook->getType(), $data, $event, $webhook, $webhookDelivery->getId()))
                ->setEventName($event)
                ->setSignature(
                    $this->webhookFactory->createSignature(
                        $webhookDelivery->getRequestBody(),
                        $webhook->getSecret()
                    )
                );

            $this->entityManager->persist($webhookDelivery);
        }

        $this->entityManager->flush();
    }
}