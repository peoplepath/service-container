<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithNullableParam
{
    public function __construct(public First|null $first)
    {
    }
}
