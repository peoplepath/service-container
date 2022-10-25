<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use Closure;
use IW\ServiceContainer;
use ReflectionFunction;
use ReflectionParameter;

class CallableFactory implements ServiceFactory
{
    use ArgumentBuilder;

    /** @var callable */
    private $factory;

    /** @var array<int, array{class-string, bool, mixed}> */
    private array $ids;

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

        return ($this->factory)(...$args);
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
        $reflection = new ReflectionFunction(Closure::fromCallable($this->factory));

        return $reflection->getParameters();
    }
}
