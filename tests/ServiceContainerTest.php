<?php

declare(strict_types=1);

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
    public function testGettingNonExistingClass(string $id) : void
    {
        $container = new ServiceContainer();

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service object not found, id: ' . $id);
        $container->get($id);
    }

    public function testThatExceptionNotRelatedWithServiceMakingAreDisclosed() : void
    {
        $container = new ServiceContainer();

        $this->expectException('Exception');
        $this->expectExceptionMessage('blah blah');
        $container->get(ClassWithFalseConstructor::class);
    }

    public function testSettingAndUnsettingAService() : void
    {
        $container = new ServiceContainer();

        $service            = new stdClass();
        $container->set($id = random_bytes(10), $service);

        $this->assertSame($service, $container->get($id));

        $this->assertTrue($container->unset($id));

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service object not found, id: ' . $id);
        $container->get($id);
    }

    public function testUnsettingUnknownService() : void
    {
        $container = new ServiceContainer();

        $this->assertFalse($container->unset('ImNotSingleton'));
    }

    public function testImplicitSingleton() : void
    {
        $container = new ServiceContainer(['singletons' => true]);

        // you always get singleton of same class (id)
        $service = $container->get('Foo');
        $this->assertSame($service, $container->get('Foo'));
    }

    /**
     * @testWith ["AliasOfFoo", "Foo"]
     *            ["AliasOfBar", "Bar"]
     */
    public function testServiceAliasing(string $alias, string $id) : void
    {
        $container = new ServiceContainer();

        $container->alias($alias, $id);
        $this->assertEquals($container->get($alias), $container->get($id));
    }

    public function testExplicitSingleton() : void
    {
        $container = new ServiceContainer(['singletons' => false]);

        // by default it's generating always fresh instance
        $service = $container->get('Foo');
        $this->assertNotSame($service, $container->get('Foo'));

        // after method singleton returns always a singleton
        $container->singleton('Foo');
        $service = $container->get('Foo');
        $this->assertSame($service, $container->get('Foo'));
    }

    public function testBindingCustomFactory() : void
    {
        $container = new ServiceContainer();

        $bar = $container->get('Bar');

        $container->bind($id = uniqid(), static function (ServiceContainer $container) use ($bar) {
            $service      = new stdClass();
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
    public function testHasMethod(string $id, bool $has) : void
    {
        $container = new ServiceContainer();

        if ($has) {
            $this->assertTrue($container->has($id));
        } else {
            $this->assertFalse($container->has($id));
        }
    }

    public function testResolveFunction() : void
    {
        $container = new ServiceContainer();

        $params = $container->resolve('foo');
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertInstanceOf('Foo', $params[0]);
    }

    public function testResolveMethod() : void
    {
        $container = new ServiceContainer();

        $foo = $container->get('Foo');

        $params = $container->resolve([$foo, 'bar']);
        $this->assertIsArray($params);
        $this->assertCount(0, $params);
    }

    public function testResolveStaticMethod() : void
    {
        $container = new ServiceContainer();

        $params = $container->resolve('Bar::hello');
        $this->assertIsArray($params);
        $this->assertCount(0, $params);

        $params = $container->resolve(['Bar', 'hello']);
        $this->assertIsArray($params);
        $this->assertCount(0, $params);
    }

    public function testResolveClosure() : void
    {
        $container = new ServiceContainer();

        $hello = static function (string $who) {
            return Bar::hello($who);
        };

        $params = $container->resolve($hello, ['who' => 'Alice']);
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertSame('Alice', $params[0]);
    }

    public function testResolveInvokable() : void
    {
        $container = new ServiceContainer();

        $foo = $container->get('Foo');

        $params = $container->resolve($foo);
        $this->assertIsArray($params);
        $this->assertCount(1, $params);
        $this->assertInstanceOf('Bar', $params[0]);
    }

    public function testCannotResolveScalarTypes() : void
    {
        $container = new ServiceContainer();

        $this->expectException(UnsupportedAutowireParamException::class);
        $this->expectExceptionMessage('Unsupported type hint for param: Parameter #0 [ <required> string $who ]');
        $container->resolve('\hello');
    }

    public function testCannotResolveMissingHintTypes() : void
    {
        $container = new ServiceContainer();

        $this->expectException(UnsupportedAutowireParamException::class);
        $this->expectExceptionMessage('No type hint for param: Parameter #1 [ <required> $value ]');
        $container->resolve('\pass');
    }

    public function testGetForBuildInClass() : void
    {
        $container = new ServiceContainer();

        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    public function testGetForAClass() : void
    {
        $container = new ServiceContainer();

        $this->assertInstanceOf('Bar', $container->get('Bar'));
    }

    public function testGetForBuildInClassWithDisabledAutowiring() : void
    {
        $container = new ServiceContainer(['autowire' => false]);

        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    /**
     * Due to a bug in PHP reflection which conceal the error
     */
    public function testMakeForClassWithSyntaxError() : void
    {
        $container = new ServiceContainer();

        $this->expectException(ReflectionError::class);
        $container->make(Bum::class);
    }

    public function testThatInterfaceCannotBeAutowired() : void
    {
        $container = new ServiceContainer();

        $this->expectException(CannotAutowireInterfaceException::class);
        $this->expectExceptionMessage('Cannot autowire interface: CacheAdapterInterface');
        $container->get(Cache::class);
    }

    public function testThatScalarCannotBeAutowired() : void
    {
        $container = new ServiceContainer();

        $this->expectException(UnsupportedAutowireParamException::class);
        $this->expectExceptionMessage('Unsupported type hint for param: Parameter #0 [ <required> int $userId ]');
        $container->get(ClassWithUnsupportedParam::class);
    }

    public function testCannotMakeServiceWhenAutowiringIsDisabled() : void
    {
        $container = new ServiceContainer(['autowire' => false]);

        $this->expectException(CannotMakeServiceException::class);
        $this->expectExceptionMessage('Cannot make service, id: Foo');
        $container->make(Foo::class);
    }

    public function testGettingUnknownServiceWhenAutowiringIsDisabled() : void
    {
        $container = new ServiceContainer(['autowire' => false]);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service object not found, id: WhoKnows');
        $container->get('WhoKnows');
    }

    public function testProperFailWhenFactoryIsDefinedBadly() : void
    {
        $container = new ServiceContainer();

        $container->bind('Poo', static function () : void {
            // this factory returns nothing
        });

        $this->expectException(EmptyResultFromFactoryException::class);
        $this->expectExceptionMessage('Empty result from factory, id: Poo');
        $container->make('Poo');
    }

    public function testHasMethodReturnsFalseNotAnExceptionIfAutowiringIsDisabled() : void
    {
        $container = new ServiceContainer(['autowire' => false]);

        $this->assertFalse($container->has(Foo::class));
    }

    public function testResolvingArbitraryFactoryParams() : void
    {
        $container = new ServiceContainer();

        $container->bind('get_me_a_foo', static function (Foo $foo) {
            return $foo;
        });

        $this->assertInstanceOf(Foo::class, $container->get('get_me_a_foo'));
    }
}

interface CacheAdapterInterface
{
}

class CacheAdapter implements CacheAdapterInterface
{
}

class Cache
{
    function __construct(CacheAdapterInterface $adapter)
    {
    }
}

function foo(Foo $foo)
{
    return $foo;
}

class Foo
{
    public function __construct(Bar $bar)
    {
        $this->_bar = $bar;
    }

    public function bar() : Bar
    {
        return $this->_bar;
    }

    public function __invoke(Bar $bar)
    {
        return $bar;
    }
}

class Bar
{
    public static function hello(string $who = 'World') : string
    {
        return 'Hello ' . $who;
    }
}

class Bum
{
    public function __construct(ClassWithSyntaxError $lovelyError)
    {
    }
}

class ClassWithFalseConstructor
{
    function __construct()
    {
        throw new Exception('blah blah');
    }
}

class ClassWithUnsupportedParam
{
    function __construct(int $userId)
    {
    }
}

function hello(string $who) : string
{
    return 'Hello ' . $who;
}

function pass(Foo $foo, $value) : Closure
{
}
