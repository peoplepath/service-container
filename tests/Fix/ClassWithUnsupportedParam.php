<?php // phpcs:disable

namespace IW\Fix;

class ClassWithUnsupportedParam
{
    /** @var int */
    public $userId;

    function __construct(int $userId)
    {
        $this->userId = $userId;
    }
}
