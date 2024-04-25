<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithVariadicConstructor
{
    public readonly array $deps;

    public function __construct(Alias ...$deps) // @phpstan-ignore-next-line
    {
        $this->deps = $deps;
    }
}
