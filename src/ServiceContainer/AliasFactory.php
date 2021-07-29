<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;

class AliasFactory implements ServiceFactory
{
    /** @var string */
    private $classname;

    public function __construct(string $classname)
    {
        $this->classname = $classname;
    }

    /**
     * Creates instance of simple class with no parameters
     *
     * @return mixed
     */
    public function __invoke(ServiceContainer $container)
    {
        return $container->get($this->classname);
    }
}
