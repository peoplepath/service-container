<?php

namespace spec\IW;

use IW\ServiceContainer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use IW\ServiceContainer\CannotAutowireInterfaceException;
use IW\ServiceContainer\ServiceNotFoundException;

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
        $this->has('DateTimeImmutable')->shouldReturn(true);
    }

    function it_fail_autowiring_for_interface(): void
    {
        $this->shouldThrow(CannotAutowireInterfaceException::class)->duringGet('DateTimeInterface');
    }

    function it_fail_for_non_existing_class(): void
    {
        $this->shouldThrow(ServiceNotFoundException::class)->duringGet('NoClassHere');
    }

    function it_autowire_any_dependency_tree(): void
    {
        $this->get(Baz::class)->shouldBeAnInstanceOf(Baz::class);
    }

}

class Foo {}

class Bar {
    function __construct(Foo $foo) {}
}

class Baz {
    function __construct(Bar $bar, Foo $foo) {}
}
