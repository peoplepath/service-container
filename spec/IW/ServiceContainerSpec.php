<?php

namespace spec\IW;

use IW\ServiceContainer;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use IW\ServiceContainer\CannotAutowireInterfaceException;
use IW\ServiceContainer\EmptyResultFromFactoryException;
use IW\ServiceContainer\ServiceNotFoundException;

class ServiceContainerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ServiceContainer::class);
    }

    function it_implements_psr11()
    {
        $this->shouldImplement(ContainerInterface::class);
    }

    function it_returns_any_initializable_with_disabled_autowire()
    {
        $this->beConstructedWith(false);
        $this->get('DateTime')->shouldBeAnInstanceOf('DateTime');
    }

    function it_knows_any_initializable_with_disabled_autowire()
    {
        $this->beConstructedWith(false);
        $this->has('DateTimeImmutable')->shouldReturn(true);
    }

    function it_fail_autowiring_for_interface()
    {
        $this->shouldThrow(CannotAutowireInterfaceException::class)->duringGet('DateTimeInterface');
    }

    function it_fail_for_non_existing_class()
    {
        $this->shouldThrow(ServiceNotFoundException::class)->duringGet('NoClassHere');
    }

    function it_autowire_any_dependency_tree()
    {
        $this->get(Baz::class)->shouldBeAnInstanceOf(Baz::class);
    }

    function it_creates_singletons_by_default()
    {
        $bar = $this->getWrappedObject()->get(Bar::class);
        $this->get(Bar::class)->shouldBe($bar);
    }

    function it_generates_fresh_instances_when_singleton_is_disabled()
    {
        $this->setSingleton(Bar::class, false);
        $bar = $this->getWrappedObject()->get(Bar::class);
        $this->get(Bar::class)->shouldNotBe($bar);
    }

    function it_accept_non_class_id_if_factory_is_defined()
    {
        $this->setFactory('logger', function () {
            return new Foo;
        });

        $this->get('logger')->shouldBeAnInstanceOf(Foo::class);
    }

    function it_fail_when_factory_result_is_empty() {
        $this->setFactory('nothing', function () {});

        $this->shouldThrow(EmptyResultFromFactoryException::class)->duringGet('nothing');
    }

}

class Foo {}

class Bar {
    function __construct(Foo $foo) {}
}

class Baz {
    function __construct(Bar $bar, Foo $foo) {}
}

