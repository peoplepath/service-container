<?php

namespace IW\ServiceContainer;

use Psr\Container\ContainerExceptionInterface;

class Exception extends \Exception implements ContainerExceptionInterface
{

    public function __construct(string $message = null, \Exception $previous = null) {
        parent::__construct($message, 0, $previous);
    }

}
