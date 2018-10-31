<?php

namespace IW\ServiceContainer;

class EmptyResultFromFactoryException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct('Empty result from factory, id: ' . $id);
    }
}
