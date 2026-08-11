<?php
declare(strict_types=1);
namespace Framework\Kernel\Subscribers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Framework\Configs\ConfigCore;
use Framework\Core\Container\Container;
use Framework\Core\EventManager;
use Framework\Kernel\KernelEventSubscriberInterface;
use Framework\Kernel\KernelOptions;
use Framework\Utils\DoctrineUuidType;
use Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mysql\JsonContains;

class OrmSubscriber implements KernelEventSubscriberInterface
{
    public function __construct(
        protected ConfigCore $configCore,
        protected Container $container,
        protected KernelOptions $options,
    ) {
    }

    public function subscribe(EventManager $eventManager): void
    {
        $eventManager->registerEvent('kernel.configure_orm', function () {
            $namespaceConfig = $this->configCore->getConfig('namespace');
            $ormConfig = $this->configCore->getConfig('orm');
            if (!$ormConfig->get('orm.use')) {
                return;
            }

            $config = ORMSetup::createAttributeMetadataConfiguration(
                paths: [$namespaceConfig->get('namespace.entity.path')],
                isDevMode: true,
            );

            $config->addCustomStringFunction(JsonContains::FUNCTION_NAME, JsonContains::class);


            $connection = DriverManager::getConnection($ormConfig->get('orm.connection'), $config);
            $entityManager = new EntityManager($connection, $config);

            $this->container->bind(Connection::class, function () use ($connection) {
                return $connection;
            });

            $this->container->bind(EntityManager::class, function () use ($entityManager) {
                return $entityManager;
            });

            if (!Type::hasType('uuid_binary')) {
                Type::addType('uuid_binary', DoctrineUuidType::class);
            }

            if ($this->options->debugBar) {
                $this->options->debugBar['messages']->addMessage('ORM started', 'debug');
            }
        });
    }
}
