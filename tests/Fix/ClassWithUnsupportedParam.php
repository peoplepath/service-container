<?php

// phpcs:disable

namespace IW\Fix;

class ClassWithUnsupportedParam
{
    /** @var int */
    public $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }
}
