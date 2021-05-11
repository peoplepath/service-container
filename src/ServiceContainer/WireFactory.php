<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;

use function array_map;

final class WireFactory implements ServiceFactory
{
    /** @var string[] $dependencies */
    private array $dependencies;
    private string $classname;

    public function __construct(string $classname, string ...$dependencies)
    {
        $this->classname    = $classname;
        $this->dependencies = $dependencies;
    }

    /**
     * Method will create new instance of a service on call
     */
    public function __invoke(ServiceContainer $container): mixed
    {
        $classname    = $this->classname;
        $dependencies = array_map(
            static function ($dependency) use ($container) {
                return $container->get($dependency);
            },
            $this->dependencies,
        );

        return new $classname(...$dependencies);
    }
}
