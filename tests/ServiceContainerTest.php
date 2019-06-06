<?php

use IW\ClassWithSyntaxError;
use IW\ServiceContainer;
use IW\ServiceContainer\CannotAutowireInterfaceException;
use IW\ServiceContainer\CannotMakeServiceException;
use IW\ServiceContainer\EmptyResultFromFactoryException;
use IW\ServiceContainer\ReflectionError;
use IW\ServiceContainer\ServiceNotFoundException;
use IW\ServiceContainer\UnsupportedAutowireParamException;
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

    function testThatExceptionNotRelatedWithServiceMakingAreDisclosed() {
        $container = new ServiceContainer;

        $this->expectException('Exception');
        $this->expectExceptionMessage('blah blah');
        $container->get(ClassWithFalseConstructor::class);
    }

    function testSettingAndUnsettingAService() {
        $container = new ServiceContainer;

        $service = new stdClass;
        $container->set($id = random_bytes(10), $service);

        $this->assertSame($service, $container->get($id));

        $this->assertTrue($container->unset($id));

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service object not found, id: ' . $id);
        $container->get($id);
    }

    function testUnsettingUnknownService() {
        $container = new ServiceContainer;

        $this->assertFalse($container->unset('ImNotSingleton'));
    }

    function testImplicitSingleton() {
        $container = new ServiceContainer(['singletons' => true]);

        // you always get singleton of same class (id)
        $service = $container->get('Foo');
        $this->assertSame($service, $container->get('Foo'));
    }

    /**
     * @testWith ["AliasOfFoo", "Foo"]
    *            ["AliasOfBar", "Bar"]
     */
    function testServiceAliasing(string $alias, string $id) {
        $container = new ServiceContainer;

        $container->alias($alias, $id);
        $this->assertEquals($container->get($alias), $container->get($id));
    }

    function testExplicitSingleton() {
        $container = new ServiceContainer(['singletons' => false]);

        // by default it's generating always fresh instance
        $service = $container->get('Foo');
        $this->assertNotSame($service, $container->get('Foo'));

        // after method singleton returns always a singleton
        $container->singleton('Foo');
        $service = $container->get('Foo');
        $this->assertSame($service, $container->get('Foo'));
    }

    function testBindingCustomFactory() {
        $container = new ServiceContainer;

        $bar = $container->get('Bar');

        $container->bind($id = uniqid(), function ($container) use ($bar) {
            $service = new stdClass;
            $service->foo = $container->get('Foo');
            $service->bar = $bar;

            return $service;
        });

        $service = $container->get($id);

        $this->assertIsObject($service);
        $this->assertInstanceOf('stdClass', $service);
        $this->assertObjectHasAttribute('foo', $service);
        $this->assertInstanceOf('Foo', $service->foo);
        $this->assertObjectHasAttribute('bar', $service);
        $this->assertSame($bar, $service->bar);
    }

    /**
     * @testWith ["NotExists", false]
     *           ["Foo", true]
     *           ["Bar", true]
     */
    function testHasMethod(string $id, bool $has) {
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

    function testCannotResolveScalarTypes() {
        $container = new ServiceContainer;

        $this->expectException(UnsupportedAutowireParamException::class);
        $this->expectExceptionMessage('Unsupported type hint for param: Parameter #0 [ <required> string $who ]');
        $container->resolve('\hello');
    }

    function testCannotResolveMissingHintTypes() {
        $container = new ServiceContainer;

        $this->expectException(UnsupportedAutowireParamException::class);
        $this->expectExceptionMessage('No type hint for param: Parameter #1 [ <required> $value ]');
        $container->resolve('\pass');
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
     */
    function testMakeForClassWithSyntaxError() {
        $container = new ServiceContainer;

        $this->expectException(ReflectionError::class);
        $container->make(Bum::class);
    }

    function testThatInterfaceCannotBeAutowired() {
        $container = new ServiceContainer;

        $this->expectException(CannotAutowireInterfaceException::class);
        $this->expectExceptionMessage('Cannot autowire interface: CacheAdapterInterface');
        $container->get(Cache::class);
    }

    function testThatScalarCannotBeAutowired() {
        $container = new ServiceContainer;

        $this->expectException(UnsupportedAutowireParamException::class);
        $this->expectExceptionMessage('Unsupported type hint for param: Parameter #0 [ <required> int $userId ]');
        $container->get(ClassWithUnsupportedParam::class);
    }

    function testCannotMakeServiceWhenAutowiringIsDisabled() {
        $container = new ServiceContainer(['autowire' => false]);

        $this->expectException(CannotMakeServiceException::class);
        $this->expectExceptionMessage('Cannot make service, id: Foo');
        $container->make(Foo::class);
    }

    function testGettingUnknownServiceWhenAutowiringIsDisabled() {
        $container = new ServiceContainer(['autowire' => false]);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service object not found, id: WhoKnows');
        $container->get('WhoKnows');
    }

    function testProperFailWhenFactoryIsDefinedBadly() {
        $container = new ServiceContainer;

        $container->bind('Poo', function () {
            // this factory returns nothing
        });

        $this->expectException(EmptyResultFromFactoryException::class);
        $this->expectExceptionMessage('Empty result from factory, id: Poo');
        $container->make('Poo');
    }

    function testHasMethodReturnsFalseNotAnExceptionIfAutowiringIsDisabled() {
        $container = new ServiceContainer(['autowire' => false]);

        $this->assertFalse($container->has(Foo::class));
    }
}

interface CacheAdapterInterface {}

class CacheAdapter implements CacheAdapterInterface {}

class Cache {

    function __construct(CacheAdapterInterface $adapter) {}

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

class ClassWithUnsupportedParam {
    function __construct(int $userId) {

    }
}

function hello(string $who): string {
    return 'Hello ' . $who;
}

function pass(Foo $foo, $value): \Closure {

}
