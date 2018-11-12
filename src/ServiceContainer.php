<?php declare(strict_types=1);

namespace IW;

use Psr\Container\ContainerInterface;
use IW\ServiceContainer\CannotAutowireInterfaceException;
use IW\ServiceContainer\EmptyResultFromFactoryException;
use IW\ServiceContainer\ServiceNotFoundException;
use IW\ServiceContainer\UnsupportedAutowireParamException;

class ServiceContainer implements ContainerInterface
{
    /** @var bool */
    protected $autowireEnabled = true;

    /** @var bool */
    protected $defaultSingletons = true;

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
     *
     * @param array $options options [autowire => bool, singletons => bool]
     */
    public function __construct(array $options=[]) {
        $this->autowireEnabled   = (bool) ($options['autowire'] ?? true);    // autowire by default
        $this->defaultSingletons = (bool) ($options['singletons'] ?? false); // don't create singletons by default
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
        if (!isset($this->factories[$id])) {
            try {
                // first try create instance naively
                $instance = ($this->factories[$id] = static::buildSimpleFactory($id))();
            } catch (\Throwable $t) {
                // cannot create instance naively for some reason, keep going and try create factory then
                if (!$this->autowireEnabled) {
                    throw new ServiceNotFoundException($id, $t);
                }

                $this->factories[$id] = self::buildFactory($id);
            }

        }

        $instance = $instance ?? $this->factories[$id]($this, $id);

        if (null === $instance) {
            throw new EmptyResultFromFactoryException($id);
        }

        return $instance;
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

    private static function buildSimpleFactory($classname)
    {
        return static function () use ($classname) {
            return new $classname;
        };
    }

    private static function buildAliasFactory($id) {
        return static function ($container) use ($id) {
            return $container->get($id);
        };
    }

}
