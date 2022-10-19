<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use Exception;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ServiceNotFound extends Exception implements NotFoundExceptionInterface
{
    public function __construct(string $id, Throwable|null $previous = null)
    {
        parent::__construct('Service object not found, id: ' . $id, 1, $previous);
    }
}
