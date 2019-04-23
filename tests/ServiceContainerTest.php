<?php

use IW\ServiceContainer;
use PHPUnit\Framework\TestCase;

class ServiceContainerTest extends TestCase
{

    public function testResolveFunction() {
        $container = new ServiceContainer;

        $this->assertInstanceOf('Foo', $container->resolve('foo'));
    }

    public function testResolveMethod() {
        $container = new ServiceContainer;

        $foo = $container->get('Foo');

        $this->assertInstanceOf('Bar', $container->resolve([$foo, 'bar']));
    }

    public function testResolveStaticMethod() {
        $container = new ServiceContainer;

        $this->assertSame('Hello World', $container->resolve('Bar::hello'));
        $this->assertSame('Hello World', $container->resolve(['Bar', 'hello']));
    }

    public function testResolveClosure() {
        $container = new ServiceContainer;

        $hello = function (string $who) {
            return Bar::hello($who);
        };

        $this->assertSame('Hello Alice', $container->resolve($hello, ['who' => 'Alice']));
    }

    public function testResolveInvokable() {
        $container = new ServiceContainer;

        $this->assertInstanceOf('Bar', $container->resolve($container->resolve('foo')));
    }

    public function testGetForBuildInClass() {
        $container = new ServiceContainer;

        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    public function testGetForAClass() {
        $container = new ServiceContainer;

        $this->assertInstanceOf('Bar', $container->get('Bar'));
    }

    public function testGetForBuildInClassWithDisabledAutowiring() {
        $container = new ServiceContainer(['autowire' => false]);

        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }
}

function foo(Foo $foo) {
    return $foo;
}

class Foo {
    public function __construct(Bar $bar) {
        $this->_bar = $bar;
    }

    public function bar(): Bar {
        return $this->_bar;
    }

    public function __invoke() {
        return $this->bar();
    }
}

class Bar {
    public static function hello(string $who='World'): string {
        return 'Hello ' . $who;
    }
}
