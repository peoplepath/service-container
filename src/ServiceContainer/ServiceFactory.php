<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;

interface ServiceFactory
{
    /**
     * Method will create new instance of a service on call
     */
    public function __invoke(ServiceContainer $container): mixed;
}
