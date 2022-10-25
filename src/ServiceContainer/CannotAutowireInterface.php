<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use ReflectionClass;

class CannotAutowireInterface extends Exception
{
    /** @param ReflectionClass<object> $reflectionClass */
    public function __construct(ReflectionClass $reflectionClass)
    {
        parent::__construct('Cannot autowire interface: ' . $reflectionClass->getName());
    }
}
