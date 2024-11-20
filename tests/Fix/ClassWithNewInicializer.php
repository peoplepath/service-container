<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithNewInicializer
{
    public function __construct(public readonly Alias $alias = new Zero())
    {
    }
}
