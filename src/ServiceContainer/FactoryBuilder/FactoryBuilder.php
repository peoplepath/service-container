<?php

declare(strict_types=1);

namespace IW\ServiceContainer\FactoryBuilder;

interface FactoryBuilder
{
    /**
     * Builds ServiceContainer factory for given ID (a class probably)
     */
    public function buildFactory(string $id) : callable;
}
