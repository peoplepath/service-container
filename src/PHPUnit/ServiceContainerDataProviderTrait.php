<?php

declare(strict_types=1);

namespace IW\PHPUnit;

trait ServiceContainerDataProviderTrait
{
    use ServiceContainerProviderTrait;

    /**
     * It's called before each test to resolve its dependencies defined
     *
     * @return void
     */
    public function ServiceContainer(string $method) : array
    {
        return [$this->getServiceContainer()->resolve([$this, $method])];
    }
}
