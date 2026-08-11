<?php
declare(strict_types=1);
namespace Tests\Core\Events;

use Framework\Configs\ConfigCore;
use Framework\Core\Container\Container;
use Doctrine\ORM\EntityManager;
use Framework\Router\Router;
use Tests\Core\TestsManager;
use Tests\Core\TestDatabase;

class InitializeTestsEvent
{
    protected ?Container $container;
    protected TestsManager $testsManager;

    /**
     * Event handler receives an array with data keys (container, kernelContext, router)
     * @param array $data
     * @return void
     */
    public function handle(array $data): void
    {
        $this->container = $data['container'] ?? null;

        // If router is provided, bind it into the container so tests can resolve it
        $router = $data['router'] ?? null;
        if ($router instanceof Router && $this->container instanceof Container) {
            $this->container->singleton(Router::class, function () use ($router) {
                return $router;
            });
        }

        $this->prepareTestDatabase();

        $this->testsManager = new TestsManager($this->container, $router);
        $this->testsManager->run();
    }

    protected function prepareTestDatabase(): void
    {
        if (!$this->container instanceof Container) {
            return;
        }

        /** @var ConfigCore|null $configCore */
        $configCore = $this->container->has(ConfigCore::class) ? $this->container->resolve(ConfigCore::class) : null;
        /** @var EntityManager|null $entityManager */
        $entityManager = $this->container->has(EntityManager::class) ? $this->container->resolve(EntityManager::class) : null;

        if (!$configCore instanceof ConfigCore || !$entityManager instanceof EntityManager) {
            return;
        }

        $testDb = new TestDatabase($entityManager, $configCore);
        $testDb->rebuild();

        $fixtureFile = __DIR__ . '/../../Fixtures/test_database.php';
        $testDb->loadFixtureFile($fixtureFile);
    }
}