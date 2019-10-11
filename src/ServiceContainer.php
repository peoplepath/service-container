<?php declare(strict_types=1);

namespace IW;

use IW\ServiceContainer\CannotAutowireInterfaceException;
use IW\ServiceContainer\CannotMakeServiceException;
use IW\ServiceContainer\EmptyResultFromFactoryException;
use IW\ServiceContainer\ReflectionError;
use IW\ServiceContainer\ServiceNotFoundException;
use IW\ServiceContainer\UnsupportedAutowireParamException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ServiceContainer implements ContainerInterface
{
    /** @var bool */
    protected $autowireEnabled = true;

    /** @var bool */
    protected $defaultSingletons = true;

    /** @var bool */
    protected $eagerwireEnabled = false;

    /** @var callable[] */
    private $factories = [];

    /** @var mixed[] */
    private $instances = [];

    /**
     * Options:
     * - autowire   TRUE will enable autowiring (container will try to resolve
     *              dependencies in constructor automatically), FALSE will not
     *              resolve any dependencies but container still be able resolve
     *              instances of classes without dependencies
     * - singletons when TRUEcontainer will save resolved instances by default,
     *              container will resolve any subsequent dependencies with the
     *              same instance (singleton), FALSE will not save instances
     *              therefore container returns always fresh instance
     * - eagerwire  TRUE will always try to resolve optional dependencies, FALSE
     *              will omit resolution of optional parameters
     *
     * @param array $options options [autowire => bool, singletons => bool]
     */
    public function __construct(array $options=[]) {
        $this->autowireEnabled   = (bool) ($options['autowire'] ?? true);    // autowire by default
        $this->defaultSingletons = (bool) ($options['singletons'] ?? false); // don't create singletons by default
        $this->eagerwireEnabled  = (bool) ($options['eagerwire'] ?? false);  // don't resolve optional args
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
            return $this->instances[$id]; // try load a singleton if saved
        }

        if ($id === static::class) {
            return $this; // resolve container by itself
        }

        $instance = $this->make($id);

        if ($this->defaultSingletons) {
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
        // do not attempt create a service when "autowire" is disabled
        if (!$this->autowireEnabled) {
            return isset($this->instances[$id]);
        }

        try {
            $this->get($id); // attempt create a service
        } catch (ServiceNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * Sets an alias for a dependency, it's useful for binding implementations
     * to a interface. Container will resolve alias as late as possible.
     *
     * Example:
     * <code>
     * interface Logger {}
     * class FileLogger implements Logger {}
     * class Service { function __construct(Logger $logger) {} }
     *
     * $container->alias(Logger::class, FileLogger::class);
     * $service = $container->get(Service::class);
     * </code>
     *
     * @param string $alias an ID for aliased dependency
     * @param string $id    an ID of instance which will be alias resolve with
     *
     * @return void
     */
    public function alias(string $alias, string $id): void
    {
        $this->factories[$alias] = self::buildAliasFactory($id);
    }

    /**
     * Bind a factory to an instance, it's useful for resolving complex dependencies
     * where manually (eg. database connection)
     *
     *
     *
     * @param string   $id      ID of entry we want to define factory for
     * @param callable $factory a callable which returns new instance if called
     *                          callable will receive two arguments:
     *                          $container - this container
     *                          $id - ID that was called to create
     *
     * @return void
     */
    public function bind(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Makes a new instance of a service. Dependencies are resolved from the container.
     *
     * @param string $id ID of entry we want to create new instance of
     *
     * @return mixed
     */
    public function make(string $id)
    {
        try {
            if (!isset($this->factories[$id])) {
                try {
                    // first try create instance naively
                    $instance = ($this->factories[$id] = static::buildSimpleFactory($id))();
                } catch (\ArgumentCountError|\Error $error) {
                    unset($this->factories[$id]);

                    // cannot create instance naively for some reason, keep going and try create factory then
                    if (!$this->autowireEnabled) {
                        throw $error;
                    }

                    $this->factories[$id] = self::buildFactory($id);
                }
            }

            $instance = $instance ?? $this->factories[$id](...$this->resolve($this->factories[$id]));

            if (null === $instance) {
                throw new EmptyResultFromFactoryException($id);
            }
        } catch (\Throwable $error) {
            $this->handleError($id, $error);
        }

        return $instance;
    }

    /**
     * Resolve dependencies by container, or with given arguments
     *
     * @param callable $callable a callable to resolve
     * @param array    $args     associative array of optional arguments
     *
     * @return mixed a result of given callable
     */
    public function resolve(callable $callable, array $args=[])
    {
        if ($callable instanceof \Closure || (is_string($callable) && function_exists($callable))) {
            $reflection = new \ReflectionFunction($callable);
        } elseif (is_string($callable)) {
            $reflection = new \ReflectionMethod($callable);
        } elseif (is_object($callable) && ($reflection = new \ReflectionObject($callable))->hasMethod('__invoke')) {
            $reflection = $reflection->getMethod('__invoke');
        } else {
            $reflection = new \ReflectionMethod(...$callable);
        }

        $params = [];
        foreach ($reflection->getParameters() as $param) {
            if ($param->isOptional() && !$this->eagerwireEnabled) {
                break;
            } elseif (array_key_exists($name = $param->getName(), $args)) {
                $params[] = $args[$name];
            } elseif (($type = $param->getType()) && ! $type->isBuiltin()) {
                $params[] = $this->get((string) $type);
            } else {
                throw new UnsupportedAutowireParamException($param);
            }
        }

        return $params;
    }

    /**
     * Sets given entry as a singleton
     *
     * @param string $id    ID of entry
     * @param mixed  $entry actual entry
     *
     * @return void
     */
    public function set(string $id, $entry): void
    {
        $this->instances[$id] = $entry;
    }

    /**
     * Mark particular ID to be a singleton, this is useful when global singletons
     * are disabled but you still few.
     *
     * Note in good design you should not need much singletons
     *
     * @param string $id ID of singleton to set
     *
     * @return void
     */
    public function singleton(string $id): void
    {
        $this->instances[$id] = $this->get($id);
    }

    /**
     * Unset a singleton with given ID, returns TRUE if singleton was set, FALSE otherwise
     *
     * @param string $id ID of singleton to unset
     *
     * @return bool
     */
    public function unset(string $id): bool {
        if (array_key_exists($id, $this->instances)) {
            unset($this->instances[$id]);
            return true;
        }

        return false;
    }

    /**
     * Handles an error when making a service
     *
     * @param string     $id    ID of entry we're creating
     * @param \Throwable $error en error
     *
     * @return void
     */
    private function handleError(string $id, \Throwable $error): void {
        if ($error instanceof NotFoundExceptionInterface) {
            throw $error;
        }

        if ($error instanceof ContainerExceptionInterface) {
            throw $error;
        }

        if ($error instanceof \ReflectionException) {
            throw new ReflectionError($error);
        }

        if (\sprintf("Class '%s' not found", $id) === $error->getMessage()) {
            throw new ServiceNotFoundException($id, $error);
        }

        if (!$this->autowireEnabled) {
            if ($error instanceof \ArgumentCountError) {
                throw new CannotMakeServiceException($id, $error);
            }
        }

        throw $error;
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

        return static function (ServiceContainer $container) use ($class, $constructor, $ids) {
            $args = [];
            foreach ($ids as $id) {
                $args[] = $container->get($id);
            }

            $instance = $class->newInstanceWithoutConstructor();
            $constructor->invokeArgs($instance, $args);

            return $instance;
        };
    }

    private static function buildSimpleFactory($classname)
    {
        return static function () use ($classname) {
            return new $classname;
        };
    }

    private static function buildAliasFactory($id) {
        return static function (ServiceContainer $container) use ($id) {
            return $container->get($id);
        };
    }

}
