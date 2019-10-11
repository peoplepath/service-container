<?php

declare(strict_types=1);

namespace IW;

use IW\Fix;

class ServiceContainerBench
{
    /** @var ServiceContainer */
    private $container;

    public function __construct()
    {
        $this->container = new ServiceContainer();
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchMake()
    {
        $container = new ServiceContainer();
        $container->make(Fix\First::class);
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchMakeWithSingleton()
    {
        $this->container->make(Fix\First::class);
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchGet()
    {
        $container = new ServiceContainer();
        $container->get(Fix\First::class);
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchGetWithSingleton()
    {
        $this->container->get(Fix\First::class);
    }
}
