<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use ReflectionClass;

class CannotAutowireInterface extends Exception
{
    public function __construct(ReflectionClass $reflectionClass)
    {
        parent::__construct('Cannot autowire interface: ' . $reflectionClass->getName());
    }
}
