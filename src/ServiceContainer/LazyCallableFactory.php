<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;
use ReflectionClass;

class LazyCallableFactory extends CallableFactory
{
    public function __construct(private string $id, callable $factory)
    {
        parent::__construct($factory);
    }

    public function __invoke(ServiceContainer $container): mixed
    {
        return new ReflectionClass($this->id)
            ->newLazyProxy(fn () => parent::__invoke($container));
    }
}
