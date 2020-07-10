<?php

declare(strict_types=1);

namespace IW;

use function array_pop;
use function sprintf;

/**
 * @BeforeMethods({"init"})
 */
class ServiceContainerBench
{
    /** @var ServiceContainer */
    private $container;

    /** @var string[] */
    private $classnames = [];

    public function __construct()
    {
        $this->container = new ServiceContainer();
    }

    public function init(): void
    {
        for ($i = 0; $i < 1000; $i++) {
            $classname = 'benchclass' . $i;

            eval(sprintf('class %s {}', $classname));

            $this->classnames[] = $classname;
        }
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchGet(): void
    {
        $container = new ServiceContainer();
        $container->get((string) array_pop($this->classnames));
    }

    /**
     * @Revs(1000)
     * @Iterations(5)
     */
    public function benchGetSingletons(): void
    {
        $this->container->get((string) array_pop($this->classnames));
    }
}
