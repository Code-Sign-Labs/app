<?php

namespace Framework\Messenger;

use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Tools\SchemaTool;
use Framework\Messenger\Entity\MessageEntity;
use Framework\Messenger\Interfaces\MessageHandlerInterface;
use Framework\Messenger\Interfaces\MessageInterface;
use Framework\Messenger\Objects\Envelope;

class MessageDispatcher
{
    protected EntityManager $entityManager;
    protected SchemaTool $schemaTool;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->schemaTool = new SchemaTool($entityManager);
        $metadata = $this->entityManager->getClassMetadata(MessageEntity::class);
        $schemaManager = $entityManager->getConnection()->createSchemaManager();

        if(in_array('messenger_messages', $schemaManager->listTableNames())) {
            return;
        }
        $this->schemaTool->createSchema([$metadata]);
    }

    /**
     * @param MessageInterface $message
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function dispatch(MessageInterface $message): void
    {
        $messageEntity = new MessageEntity();
        $messageEntity->setContent(base64_encode($message->getContent()));
        $messageEntity->setAvailableAt($message->getAvailable());
        $messageEntity->setCreatedAt(new DateTime());
        $messageEntity->setStatus(MessengerEnum::STATUS_NONE);

        $this->entityManager->persist($messageEntity);
        $this->entityManager->flush();
    }

    /**
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function callAllMessages(): void
    {
        $repository = $this->entityManager->getRepository(MessageEntity::class);
        $messages = $repository->findAll();
        foreach($messages as $message) {
            $this->callMessage($message, $this->entityManager);
        }
    }

    /**
     * @param MessageEntity $messageEntity
     * @param EntityManager $entityManager
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function callMessage(MessageEntity $messageEntity, EntityManager $entityManager): void
    {
        if($messageEntity->getStatus() === MessengerEnum::STATUS_DELIVERED or $messageEntity->getStatus() === MessengerEnum::STATUS_FAILED or $messageEntity->getStatus() === MessengerEnum::STATUS_IN_DELIVERY) return;
        $content = base64_decode($messageEntity->getContent());
        $content = Envelope::unSerialize($content);
        $currentDate = new DateTime();
        if($messageEntity->getAvailableAt() > $currentDate) {
            if($messageEntity->getStatus() === MessengerEnum::STATUS_NONE) {
                $messageEntity->setStatus(MessengerEnum::STATUS_IN_QUEUE);
                $entityManager->persist($messageEntity);
                $entityManager->flush();
                return;
            }
            return;
        }

        // Message can be delivered
        $messageEntity->setStatus(MessengerEnum::STATUS_IN_DELIVERY);
        $entityManager->persist($messageEntity);
        $entityManager->flush();

        // Delivering message
        $handler = $content->getStamps()['handler'];
        if(!($handler instanceof MessageHandlerInterface)) {
            $messageEntity->setStatus(MessengerEnum::STATUS_FAILED);
            $entityManager->persist($messageEntity);
            $entityManager->flush();
            return;
        }

        $status = $handler->handle($content);
        if(!$status) {
            $messageEntity->setStatus(MessengerEnum::STATUS_FAILED);
            $entityManager->persist($messageEntity);
            $entityManager->flush();
            return;
        }

        $messageEntity->setStatus(MessengerEnum::STATUS_DELIVERED);
        $messageEntity->setDeliveredAt(new DateTime());
        $entityManager->persist($messageEntity);
        $entityManager->flush();
    }
}