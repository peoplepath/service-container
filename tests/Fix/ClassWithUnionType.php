<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithUnionType
{
    public function __construct(private First|Fourth $dependency)
    {
    }
}
