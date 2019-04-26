<?php

use IW\ClassWithSyntaxError;
use IW\ServiceContainer;
use PHPUnit\Framework\TestCase;

class ServiceContainerTest extends TestCase
{

    public function testResolveFunction() {
        $container = new ServiceContainer;

        $params = $container->resolve('foo');
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertInstanceOf('Foo', $params[0]);
    }

    public function testResolveMethod() {
        $container = new ServiceContainer;

        $foo = $container->get('Foo');

        $params = $container->resolve([$foo, 'bar']);
        $this->assertIsArray($params);
        $this->assertCount(0, $params);
    }

    public function testResolveStaticMethod() {
        $container = new ServiceContainer;

        $params = $container->resolve('Bar::hello');
        $this->assertIsArray($params);
        $this->assertCount(0, $params);

        $params = $container->resolve(['Bar', 'hello']);
        $this->assertIsArray($params);
        $this->assertCount(0, $params);
    }

    public function testResolveClosure() {
        $container = new ServiceContainer;

        $hello = function (string $who) {
            return Bar::hello($who);
        };

        $params = $container->resolve($hello, ['who' => 'Alice']);
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertSame('Alice', $params[0]);
    }

    public function testResolveInvokable() {
        $container = new ServiceContainer;

        $foo = $container->get('Foo');

        $params = $container->resolve($foo);
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertInstanceOf('Bar', $params[0]);
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

    /**
     * Due to a bug in PHP reflection which conceal the error
     *
     * @return void
     */
    public function testGetForClassWithSyntaxError() {
        $container = new ServiceContainer;

        $this->expectException(\ReflectionException::class);
        $container->get(Bum::class);
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

    public function __invoke(Bar $bar) {
        return $bar;
    }
}

class Bar {
    public static function hello(string $who='World'): string {
        return 'Hello ' . $who;
    }
}

class Bum {
    public function __construct(ClassWithSyntaxError $lovelyError) {

    }
}
