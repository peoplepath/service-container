<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithDnfType
{
    public function __construct(private (Zero&Alias)|Fourth $dependency)
    {
    }
}
