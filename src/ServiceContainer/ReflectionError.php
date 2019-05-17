<?php

namespace IW\ServiceContainer;

class ReflectionError extends Exception
{
    public function __construct(\ReflectionException $reflectionException)
    {
        $message = $reflectionException->getMessage();

        if ($previous = $reflectionException->getPrevious()) {
            $message .= PHP_EOL . 'cased by' . PHP_EOL . $previous->getMessage();
        }

        parent::__construct($message, $reflectionException);
    }
}
