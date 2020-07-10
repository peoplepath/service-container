<?php

declare(strict_types=1);

namespace IW;

use IW\ServiceContainer\EmptyResultFromFactory;
use IW\ServiceContainer\FactoryContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ServiceContainer implements ContainerInterface
{
    /** @var FactoryContainer */
    private $factories;

    /** @var mixed[] */
    private $instances = [];

    public function __construct()
    {
        $this->factories = new FactoryContainer();
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
     */
    public function alias(string $alias, string $id): void
    {
        $this->factories->alias($alias, $id);
    }

    /**
     * Bind a factory to an instance, it's useful for resolving complex dependencies
     * where manually (eg. database connection)
     *
     * @param string   $id      ID of entry we want to define factory for
     * @param callable $factory a callable which returns new instance if called
     *                          callable will receive two arguments:
     *                          $container - this container
     *                          $id - ID that was called to create
     */
    public function bind(string $id, callable $factory): void
    {
        $this->factories->bind($id, $factory);
    }

    public function factory(string $id): callable
    {
        return $this->factories->get($id);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param class-string<T> $id Identifier of the entry to look for.
     *
     * @return T
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @template T
     */
    public function get($id) // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint,SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint,Generic.Files.LineLength.TooLong
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id]; // try load a singleton if saved
        }

        if ($id === static::class) {
            return $this; // resolve container by itself
        }

        return $this->instances[$id] = $this->make($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     */
    public function has($id) // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint,SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint,Generic.Files.LineLength.TooLong
    {
        // is existing singleton
        if (isset($this->instances[$id])) {
            return true;
        }

        // a factory exists
        if ($this->factories->has($id)) {
            return true;
        }

        // try build a factory
        try {
            $this->factory($id);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Returns saved singleton or NULL
     *
     * @return mixed|null
     */
    public function singleton(string $id)
    {
        return $this->instances[$id] ?? null;
    }

    /**
     * Makes a new instance of a service. Dependencies are resolved from the container.
     *
     * @param class-string<T> $id ID of entry we want to create new instance of
     *
     * @return T
     *
     * @template T
     */
    public function make(string $id) // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint,Generic.Files.LineLength.TooLong
    {
        $instance = $this->factory($id)($this);

        if ($instance === null) {
            throw new EmptyResultFromFactory($id);
        }

        return $instance;
    }

    /**
     * Sets given entry as a singleton
     *
     * @param string $id    ID of entry
     * @param mixed  $entry actual entry
     */
    public function set(string $id, $entry): void
    {
        $this->instances[$id] = $entry;
    }

    /**
     * Unset a singleton with given ID, returns the singleton if existed or a NULL if didn't
     *
     * @param string $id ID of singleton to unset
     *
     * @return mixed|null
     */
    public function unset(string $id)
    {
        if (isset($this->instances[$id])) {
            $instance = $this->instances[$id];

            unset($this->instances[$id]);

            return $instance;
        }

        return null;
    }
}
