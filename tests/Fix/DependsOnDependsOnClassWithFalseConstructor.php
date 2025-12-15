<?php

declare(strict_types=1);

namespace IW\Fix;

class DependsOnDependsOnClassWithFalseConstructor
{
    public function __construct(DependsOnClassWithFalseConstructor $dependency)
    {
        unset($dependency);
    }
}
