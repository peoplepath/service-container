<?php

declare(strict_types=1);

namespace IW\Fix;

class ClassWithOptionalParams
{
    public ?Fourth $fourth;
    public string $string;
    public $options;

    public function __construct(?Fourth $fourth = null, string $string='default string', $options=[])
    {
        $this->fourth  = $fourth;
        $this->string  = $string;
        $this->options = $options;
    }
}
