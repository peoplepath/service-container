<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

class EmptyResultFromFactory extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct('Empty result from factory, id: ' . $id);
    }
}
