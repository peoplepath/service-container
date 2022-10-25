<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithIntersectionType
{
    public function __construct(private Alias&Zero $dependency)
    {
    }
}
