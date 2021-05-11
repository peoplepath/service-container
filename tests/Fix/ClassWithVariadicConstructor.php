<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithVariadicConstructor
{
    public function __construct(Alias ...$deps) // @phpstan-ignore-next-line
    {
    }
}
