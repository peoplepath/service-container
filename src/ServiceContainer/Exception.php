<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use Psr\Container\ContainerExceptionInterface;
use Throwable;

abstract class Exception extends \Exception implements ContainerExceptionInterface
{
    public function __construct(string|null $message = null, Throwable|null $previous = null)
    {
        parent::__construct($message, 1, $previous);
    }
}
