<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithSomethingToSay
{
    public function __construct(private string $message)
    {
    }

    public function say(): string
    {
        return $this->message;
    }
}
