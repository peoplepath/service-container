<?php

declare(strict_types=1);

namespace IW\Fix;

class DependsOnClassWithFalseConstructor
{
    public function __construct(ClassWithFalseConstructor $dependency)
    {
        unset($dependency);
    }
}
