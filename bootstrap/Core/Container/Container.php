<?php
declare(strict_types=1);
namespace Framework\Core\Container;

use Exception;

class Container
{
    /**
     * @var array $bindings
     */
    protected array $bindings = [];

    /**
     * @var array $instances
     */
    protected array $instances = [];

    /**
     * @var DependencyInjection $dependencyInjection
     */
    protected DependencyInjection $dependencyInjection;

    public function __construct()
    {
        $this->dependencyInjection = new DependencyInjection($this);
    }

    /**
     * @param  string $abstract
     * @param  $concrete
     * @return void
     */
    public function bind(string $abstract, $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * @param  string $abstract
     * @param  $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete): void
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }

    /**
     * @param  string $abstract
     * @return mixed
     * @throws Exception
     */
    public function resolve(string $abstract): mixed
    {
        if(isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? null;

        // Quick return for some container types to avoid recursion in DI
        if ($abstract === Container::class) {
            return $this;
        }
        if ($abstract === DependencyInjection::class) {
            return $this->dependencyInjection;
        }

        if (is_callable($concrete)) {
            $object = $concrete($this);
        } elseif (is_string($concrete)) {
            $object = $this->dependencyInjection->build($concrete);
        } elseif ($concrete === null && class_exists($abstract)) {

            $object = $this->dependencyInjection->build($abstract);
        } else {

            // Throw a clear exception when a service cannot be resolved. Avoid var_dumping
            // the trace which pollutes test output.
            throw new Exception("Service [$abstract] not found in the container.");
        }

        if(array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}