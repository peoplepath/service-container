<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

class ClassnameFactory implements ServiceFactory
{
    use ArgumentBuilder;

    private ReflectionMethod|null $constructor = null;

    /** @var array<int, array{class-string, bool, mixed}> */
    private array $ids;

    /** @param class-string $classname */
    public function __construct(private string $classname)
    {
        $this->classname = $classname;
        $this->ids       = $this->resolveIds();
    }

    /**
     * Method will create new instance of a service on call
     */
    public function __invoke(ServiceContainer $container): object
    {
        $classname = $this->classname;

        try {
            $args = $this->buildArgs($this->ids, $container);
        } catch (Throwable $e) {
            throw new BrokenDependency($classname, $e);
        }

        try {
            if ($this->constructor) {
                $class    = new ReflectionClass($classname);
                $instance = $class->newInstanceWithoutConstructor();
                $this->constructor->invokeArgs($instance, $args);
            } else {
                $instance = new $classname(...$args);
            }
        } catch (Throwable $e) {
            throw new BrokenConstructor($classname, $e);
        }

        return $instance;
    }

    /**
     * Gets reflected parameters
     *
     * @return ReflectionParameter[]
     */
    private function getParams(): array
    {
        try {
            $class = new ReflectionClass($this->classname);
        } catch (ReflectionException $e) {
            throw new ServiceNotFound($this->classname, $e);
        }

        if ($class->isInterface()) {
            throw new CannotAutowireInterface($class);
        }

        $constructor = $class->getConstructor();

        // no constructor, no visibility, no params :-)
        if ($constructor === null) {
            return [];
        }

        // if constructor isn't public we will save it for later use (construction over Reflection)
        if (! $constructor->isPublic()) {
            $constructor->setAccessible(true);
            $this->constructor = $constructor;
        }

        return $constructor->getParameters();
    }
}
