<?php

namespace spec\IW;

use IW\ServiceContainer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ServiceContainerSpec extends ObjectBehavior
{
    function it_is_initializable(): void
    {
        $this->shouldHaveType(ServiceContainer::class);
    }

    function it_returns_any_initializable(): void
    {
        $this->get('DateTime')->shouldBeAnInstanceOf('DateTime');
    }
}
