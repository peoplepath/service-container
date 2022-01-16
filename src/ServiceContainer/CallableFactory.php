<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use Closure;
use IW\ServiceContainer;
use ReflectionFunction;
use ReflectionMethod;

use function function_exists;
use function is_object;
use function is_string;

class CallableFactory implements ServiceFactory
{
    use ArgumentBuilder;

    /** @var callable */
    private $factory;

    /** @var string[] */
    private $ids;

    public function __construct(callable $factory)
    {
        $this->factory = $factory;
        $this->ids     = $this->resolveIds();
    }

    /**
     * Method will create new instance of a service on call
     */
    public function __invoke(ServiceContainer $container): mixed
    {
        $args = $this->buildArgs($this->ids, $container);

        $factory = $this->factory;

        return $factory(...$args);
    }

    public function __sleep() // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
    {
        throw new SerializationFail('CallableFactory cannot be serialized');
    }

    /**
     * Gets reflected parameters
     *
     * @return ReflectionParameter[]
     */
    private function getParams(): array
    {
        if (is_string($this->factory)) {
            if (function_exists($this->factory)) {
                $reflection = new ReflectionFunction($this->factory);
            } else {
                $reflection = new ReflectionMethod($this->factory);
            }
        } elseif ($this->factory instanceof Closure) {
            $reflection = new ReflectionFunction($this->factory);
        } elseif (is_object($this->factory)) {
            $reflection = new ReflectionMethod($this->factory, '__invoke');
        } else {
            $reflection = new ReflectionMethod(...$this->factory);
        }

        return $reflection->getParameters();
    }
}
