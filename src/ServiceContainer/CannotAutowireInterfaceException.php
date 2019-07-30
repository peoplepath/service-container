<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

class CannotAutowireInterfaceException extends Exception
{
    public function __construct(string $classname)
    {
        parent::__construct('Cannot autowire interface: ' . $classname);
    }
}
