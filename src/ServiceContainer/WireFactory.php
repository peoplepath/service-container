<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;

use function array_map;

final class WireFactory implements ServiceFactory
{
    /** @var class-string[] $dependencies */
    private array $dependencies;

    /** @phpstan-param class-string $dependencies */
    public function __construct(private string $classname, string ...$dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * Method will create new instance of a service on call
     */
    public function __invoke(ServiceContainer $container): object
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
