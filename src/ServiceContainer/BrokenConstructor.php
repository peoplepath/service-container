<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use Throwable;

class BrokenConstructor extends Exception
{
    public function __construct(string $classname, Throwable $previous)
    {
        parent::__construct('Constructor class ' . $classname . ' failed', $previous);
    }
}
