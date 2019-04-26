<?php declare(strict_types=1);

namespace IW\PHPUnit;

use IW\ServiceContainer;

trait ServiceContainerProviderTrait
{
    /**
     * Returns instance of your service container
     *
     * @return ServiceContainer
     */
    abstract protected static function getServiceContainer(): ServiceContainer;

}
