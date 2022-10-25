<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;

class AliasFactory implements ServiceFactory
{
    /** @param class-string $classname */
    public function __construct(private string $classname)
    {
    }

    /**
     * Creates instance of simple class with no parameters
     */
    public function __invoke(ServiceContainer $container): mixed
    {
        return $container->get($this->classname);
    }
}
