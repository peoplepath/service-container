<?php

declare(strict_types=1);

namespace IW\Fix;

use Exception;

class ClassWithFalseConstructor
{
    protected function __construct()
    {
        throw new Exception('blah blah');
    }
}
