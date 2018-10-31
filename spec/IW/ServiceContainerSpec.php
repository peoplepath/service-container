<?php

namespace spec\IW;

use IW\ServiceContainer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;

class ServiceContainerSpec extends ObjectBehavior
{
    function it_is_initializable(): void
    {
        $this->shouldHaveType(ServiceContainer::class);
    }

    function it_implements_psr11(): void
    {
        $this->shouldImplement(ContainerInterface::class);
    }

    function it_returns_any_initializable(): void
    {
        $this->get('DateTime')->shouldBeAnInstanceOf('DateTime');
    }

    function it_knows_any_initializable(): void
    {
        $this->has('DateTime')->shouldReturn(true);
    }
}
