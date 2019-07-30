<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ServiceNotFoundException extends Exception implements NotFoundExceptionInterface
{
    public function __construct(string $id, ?Throwable $previous = null)
    {
        parent::__construct('Service object not found, id: ' . $id, $previous);
    }
}
