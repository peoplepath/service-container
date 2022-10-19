<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithNullableParam
{
    public ?First $first;

    public function __construct(?First $first)
    {
        $this->first = $first;
    }
}
