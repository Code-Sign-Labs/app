<?php

namespace Framework\Core\Container;

use Exception;
use ReflectionException;

class DependencyInjection
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * @param string $concrete
     * @return object
     * @throws ReflectionException
     * @throws Exception
     */
    public function build(string $concrete): object
    {
        $reflector = new \ReflectionClass($concrete);

        if(!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if(is_null($constructor)) {
            return new $concrete;
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach($parameters as $parameter) {
            $dependency = $parameter->getType();

            if($dependency === null) {
                throw new Exception("Cannot resolve class {$parameter->getName()} in [$concrete]");
            }

            $dependencies[] = $this->container->resolve($dependency->getName());
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}