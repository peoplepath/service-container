<?php // phpcs:disable

declare(strict_types=1);

namespace IW\Fix;

class ClassWithNoType
{
    /** @var int */
    public $userId;

    function __construct($userId)
    {
        $this->userId = $userId;
    }
}
