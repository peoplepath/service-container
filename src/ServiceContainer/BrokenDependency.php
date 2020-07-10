<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use Throwable;

class BrokenDependency extends Exception
{
    public function __construct(string $classname, Throwable $previous)
    {
        parent::__construct('Getting class ' . $classname . ' failed', $previous);
    }
}
