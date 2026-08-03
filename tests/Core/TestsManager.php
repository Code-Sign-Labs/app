<?php

namespace Tests\Core;

use Framework\Core\Container\Container;
use Framework\Router\Router;

class TestsManager
{
    protected ?Router $router;
    protected array $results = [];

    public function __construct(protected Container $container, ?Router $router = null)
    {
        $this->router = $router;
    }

    /**
     * Scan given directory for test files and instantiate test classes
     * @param string $directory
     * @return Test[]
     */
    protected function scanTestsDirectory(string $directory): array
    {
        $files = scandir($directory);
        $tests = [];

        foreach ($files as $file) {
            if ($file === "." || $file === "..") {
                continue;
            }

            if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                continue;
            }

            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $className = pathinfo($file, PATHINFO_FILENAME);
            $testClass = "\\Tests\\Units\\" . $className;
            if (!class_exists($testClass)) {
                continue;
            }

            $test = new $testClass();
            if (!($test instanceof Test)) {
                continue;
            }

            $tests[] = $test;
        }

        return $tests;
    }

    public function run(): void
    {
        $tests = $this->scanTestsDirectory(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Units');
        echo str_repeat("=", 32) . "\n";

        foreach ($tests as $test) {
            $test->setContainer($this->container);
            // Inject router if available
            if ($this->router !== null) {
                $test->setRouter($this->router);
            }

            // Auto-detect test methods starting with 'test' and run them individually so
            // one failing method does not prevent other methods from running.
            $methods = array_values(array_filter(get_class_methods($test), fn($m) => str_starts_with($m, 'test')));

            foreach ($methods as $method) {
                $class = get_class($test);
                // Remove Tests\Units\ from the $class
                $class = str_replace('Tests\\Units\\', '', $class);

                $green  = "\033[32m";
                $red    = "\033[31m";
                $yellow = "\033[33m";
                $reset  = "\033[0m";

                try {
                    $test->setUp();
                    $test->{$method}();
                    $test->tearDown();

                    $this->results[] = [
                        'test' => $class . ' @ ' . $method,
                        'status' => 'passed'
                    ];

                    echo "{$green}[PASS]{$reset} {$class} @ {$method}" . PHP_EOL;

                } catch (TestError $e) {

                    $this->results[] = [
                        'test' => $class . ' @ ' . $method,
                        'status' => 'failed',
                        'message' => $e->getMessage()
                    ];

                    echo "{$red}[FAIL]{$reset} {$class} @ {$method} - {$e->getMessage()}" . PHP_EOL;

                    if (defined('TEST_VERBOSE') && TEST_VERBOSE) {
                        echo $yellow . $e->getTraceAsString() . $reset . PHP_EOL;
                    }

                } catch (\Throwable $e) {

                    $this->results[] = [
                        'test' => $class . ' @ ' . $method,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];

                    echo "{$yellow}[ERROR]{$reset} {$class} @ {$method} - {$e->getMessage()}" . PHP_EOL;

                    if (defined('TEST_VERBOSE') && TEST_VERBOSE) {
                        echo $yellow . $e->getTraceAsString() . $reset . PHP_EOL;
                    }
                }

            }
        }

        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'passed'));
        $failed = count(array_filter($this->results, fn($r) => $r['status'] === 'failed'));
        $errors = count(array_filter($this->results, fn($r) => $r['status'] === 'error'));

        echo str_repeat("=", 32) . "\n";
        echo sprintf("Test summary: %d passed, %d failed, %d errors%s", $passed, $failed, $errors, PHP_EOL);
    }
}