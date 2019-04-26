<?php declare(strict_types=1);

namespace IW\PHPUnit;

use IW\ServiceContainer;

trait ServiceContainerTrait
{

    /**
     * It's called before each test to resolve its dependencies defined
     *
     * @return void
     */
    public function ServiceContainer(string $method): array {
        return [$this->getServiceContainer()->resolve([$this, $method])];
    }

    /**
     * Returns instance of your service container
     *
     * @return ServiceContainer
     */
    abstract protected static function getServiceContainer(): ServiceContainer;

}
