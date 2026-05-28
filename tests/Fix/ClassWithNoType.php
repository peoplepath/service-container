<?php

// phpcs:disable

declare(strict_types=1);

namespace IW\Fix;

class ClassWithNoType
{
    /** @var int */
    public $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }
}
