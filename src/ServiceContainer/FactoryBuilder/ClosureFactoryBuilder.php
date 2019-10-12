<?php

declare(strict_types=1);

namespace IW\ServiceContainer\FactoryBuilder;

use IW\ServiceContainer\CannotAutowireInterfaceException;
use IW\ServiceContainer\ServiceNotFoundException;
use IW\ServiceContainer\UnsupportedAutowireParamException;
use ReflectionClass;
use ReflectionException;

class ClosureFactoryBuilder implements FactoryBuilder
{
    /**
     * Builds ServiceContainer factory for given ID (a class probably)
     */
    public function buildFactory(string $id) : callable
    {
        try {
            $class = new ReflectionClass($id);
        } catch (ReflectionException $e) {
            throw new ServiceNotFoundException($id, $e);
        }

        if ($class->isInterface()) {
            throw new CannotAutowireInterfaceException($id);
        }

        $constructor = $class->getConstructor();
        $constructor->setAccessible(true);

        $ids = [];
        foreach ($constructor->getParameters() as $param) {
            $classReflection = $param->getClass();

            if ($classReflection === null) {
                throw new UnsupportedAutowireParamException($param);
            }

            $ids[] = $classReflection->getName();
        }

        return static function ($container) use ($class, $constructor, $ids) {
            $args = [];
            foreach ($ids as $id) {
                $args[] = $container->get($id);
            }

            $instance = $class->newInstanceWithoutConstructor();
            $constructor->invokeArgs($instance, $args);

            return $instance;
        };
    }
}
