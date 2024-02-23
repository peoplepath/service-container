<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

class CannotAutowireCompositType extends Exception
{
    /** @param class-string $id */
    public function __construct(string $id, ServiceNotFound $previous)
    {
        parent::__construct('Cannot autowire composit type: ' . $id . ', define a factory for it', $previous);
    }
}
