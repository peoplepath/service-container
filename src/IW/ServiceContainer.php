<?php

namespace IW;

use Psr\Container\ContainerInterface;
use IW\ServiceContainer\CannotAutowireInterfaceException;
use IW\ServiceContainer\EmptyResultFromFactoryException;
use IW\ServiceContainer\ServiceNotFoundException;
use IW\ServiceContainer\UnsupportedAutowireParamException;

class ServiceContainer implements ContainerInterface
{
    /** @var bool */
    protected $autowire = true;

    /** @var callable[] */
    private $factories = [];

    /** @var mixed[] */
    private $instances = [];

    public function __construct(bool $autowire = true) {
        $this->autowire = $autowire;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id) {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            try {
                return $this->instances[$id] = new $id; // first try create instance naively
            } catch (\Throwable $t) {
                // cannot create instance naively for some reason, keep going and try create factory then
            }

            if (!$this->autowire) {
                throw new ServiceNotFoundException($id);
            }

            $this->factories[$id] = self::buildFactory($id);
        }

        $instance = $this->factories[$id]($this, $id);

        if (null === $instance) {
            throw new EmptyResultFromFactoryException($id);
        }

        // save singleton if not marked
        if (!array_key_exists($id, $this->instances)) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id): bool {
        try {
            $this->get($id); // attempt create a service
        } catch (ServiceNotFoundException $e) {
            return false;
        }

        return true;
    }

    public function setFactory(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function setSingleton(string $id, bool $singleton): void
    {
        if ($singleton) {
            if (array_key_exists($id, $this->instances) && $this->instances[$id] === null) {
                unset($this->instances[$id]);
            }
        } else {
            $this->instances[$id] = null; // mark that singleton cannot be created
        }
    }

    private static function buildFactory($classname)
    {
        try {
            $class = new \ReflectionClass($classname);
        } catch (\ReflectionException $e) {
            throw new ServiceNotFoundException($classname, $e);
        }

        if ($class->isInterface()) {
            throw new CannotAutowireInterfaceException($classname);
        }

        $constructor = $class->getConstructor();
        $constructor->setAccessible(true);

        $ids = [];
        foreach ($constructor->getParameters() as $param) {
            if (($id = $param->getClass()) === null) {
                throw new UnsupportedAutowireParamException($param);
            }

            $ids[] = $id->getName();
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
