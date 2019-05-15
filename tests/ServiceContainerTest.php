<?php

use IW\ClassWithSyntaxError;
use IW\ServiceContainer;
use IW\ServiceContainer\CannotMakeServiceException;
use IW\ServiceContainer\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;

class ServiceContainerTest extends TestCase
{
    /**
     * @testWith ["NotExists"]
     *           ["Foo\\Bar"]
     *           ["\\Foo\\Bar"]
     */
    function testGettingNonExistingClass(string $id) {
        $container = new ServiceContainer;

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service object not found, id: ' . $id);
        $container->get($id);
    }

    function testGettingClassWithConstructorError() {
        $container = new ServiceContainer;

        $this->expectException(CannotMakeServiceException::class);
        $this->expectExceptionMessage('Cannot make service, id: ClassWithFalseConstructor');
        $container->get(ClassWithFalseConstructor::class);
    }

    /**
     * @testWith ["NotExists", false]
     *           ["Foo", true]
     *           ["Bar", true]
     */
    function testHas(string $id, bool $has) {
        $container = new ServiceContainer;

        if ($has) {
            $this->assertTrue($container->has($id));
        } else {
            $this->assertFalse($container->has($id));
        }
    }

    function testResolveFunction() {
        $container = new ServiceContainer;

        $params = $container->resolve('foo');
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertInstanceOf('Foo', $params[0]);
    }

    function testResolveMethod() {
        $container = new ServiceContainer;

        $foo = $container->get('Foo');

        $params = $container->resolve([$foo, 'bar']);
        $this->assertIsArray($params);
        $this->assertCount(0, $params);
    }

    function testResolveStaticMethod() {
        $container = new ServiceContainer;

        $params = $container->resolve('Bar::hello');
        $this->assertIsArray($params);
        $this->assertCount(0, $params);

        $params = $container->resolve(['Bar', 'hello']);
        $this->assertIsArray($params);
        $this->assertCount(0, $params);
    }

    function testResolveClosure() {
        $container = new ServiceContainer;

        $hello = function (string $who) {
            return Bar::hello($who);
        };

        $params = $container->resolve($hello, ['who' => 'Alice']);
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertSame('Alice', $params[0]);
    }

    function testResolveInvokable() {
        $container = new ServiceContainer;

        $foo = $container->get('Foo');

        $params = $container->resolve($foo);
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertInstanceOf('Bar', $params[0]);
    }

    function testGetForBuildInClass() {
        $container = new ServiceContainer;

        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    function testGetForAClass() {
        $container = new ServiceContainer;

        $this->assertInstanceOf('Bar', $container->get('Bar'));
    }

    function testGetForBuildInClassWithDisabledAutowiring() {
        $container = new ServiceContainer(['autowire' => false]);

        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    /**
     * Due to a bug in PHP reflection which conceal the error
     *
     * @return void
     */
    function testMakeForClassWithSyntaxError() {
        $container = new ServiceContainer;

        $this->expectException(\ReflectionException::class);
        $container->make(Bum::class);
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

class ClassWithFalseConstructor {
    function __construct() {
        throw new \Exception('blah blah');
    }
}
