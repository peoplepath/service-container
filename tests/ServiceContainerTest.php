<?php

declare(strict_types=1);

// phpcs:disable Generic.Files.LineLength.TooLong

namespace IW;

use IW\Fix\First;
use IW\ServiceContainer\BrokenConstructor;
use IW\ServiceContainer\BrokenDependency;
use PHPUnit\Framework\TestCase;
use stdClass;

use function random_bytes;
use function uniqid;
use function version_compare;

use const PHP_VERSION;

class ServiceContainerTest extends TestCase
{
    public function testGettingNonExistingClass(): void
    {
        $container = new ServiceContainer();

        $this->expectException('IW\ServiceContainer\ServiceNotFound');
        $this->expectExceptionCode(1);
        $this->expectExceptionMessage('Service object not found, id: IW\NotExists');
        $container->get('IW\NotExists');
    }

    public function testThatExceptionNotRelatedWithServiceMakingAreDisclosed(): void
    {
        $container = new ServiceContainer();

        $this->expectException('Exception');
        $this->expectExceptionMessage('blah blah');

        try {
            $container->get('IW\Fix\ClassWithFalseConstructor');
        } catch (BrokenConstructor $e) {
            throw $e->getPrevious();
        }
    }

    public function testSettingAndUnsettingAService(): void
    {
        $container = new ServiceContainer();

        $service = new stdClass();
        $container->set($id = random_bytes(10), $service);

        $this->assertSame($service, $container->get($id));

        $this->assertSame($service, $container->unset($id));

        $this->expectException('IW\ServiceContainer\ServiceNotFound');
        $this->expectExceptionMessage('Service object not found, id: ' . $id);
        $container->get($id);
    }

    public function testUnsettingUnknownService(): void
    {
        $container = new ServiceContainer();

        $this->assertNull($container->unset('ImNotSingleton'));
    }

    public function testImplicitSingleton(): void
    {
        $container = new ServiceContainer();

        // you always get singleton of same class (id)
        $service = $container->get('IW\Fix\First');
        $this->assertSame($service, $container->get('IW\Fix\First'));
    }

    public function testServiceAliasing(): void
    {
        $container = new ServiceContainer();

        $container->alias('IW\Fix\Alias', 'IW\Fix\Zero');
        $this->assertSame($container->get('IW\Fix\Alias'), $container->get('IW\Fix\Zero'));
    }

    public function testBindingCustomFactory(): void
    {
        $container = new ServiceContainer();

        $bar = $container->get('IW\Fix\Second');

        $container->bind($id = uniqid(), static function (ServiceContainer $container) use ($bar) {
            $service      = new stdClass();
            $service->foo = $container->get('IW\Fix\First');
            $service->bar = $bar;

            return $service;
        });

        $service = $container->get($id);

        $this->assertIsObject($service);
        $this->assertInstanceOf('stdClass', $service);
        $this->assertObjectHasAttribute('foo', $service);
        $this->assertInstanceOf('IW\Fix\First', $service->foo);
        $this->assertObjectHasAttribute('bar', $service);
        $this->assertSame($bar, $service->bar);
    }

    /**
     * @testWith ["IW\\NotExists", false]
     *           ["IW\\Fix\\Fourth", true]
     *           ["IW\\Fix\\Third", true]
     */
    public function testHasMethod(string $id, bool $has): void
    {
        $container = $this->createPartialMock(ServiceContainer::class, ['make']);
        $container->expects($this->never())->method('make');

        if ($has) {
            $this->assertTrue($container->has($id));
        } else {
            $this->assertFalse($container->has($id));
        }
    }

    public function testHasASingleton(): void
    {
        $container = $this->createPartialMock(ServiceContainer::class, ['make', 'factory']);
        $container->expects($this->never())->method('make');
        $container->expects($this->never())->method('factory');

        $container->set('aclass', new stdClass());
        $this->assertTrue($container->has('aclass'));
    }

    public function testHasAFactory(): void
    {
        $container = $this->createPartialMock(ServiceContainer::class, ['make', 'factory']);
        $container->expects($this->never())->method('make');
        $container->expects($this->never())->method('factory');

        $container->bind('aclass', static function () {
            return new stdClass();
        });
        $this->assertTrue($container->has('aclass'));
    }

    public function testGetForBuildInClass(): void
    {
        $container = new ServiceContainer();

        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    public function testGetForAClass(): void
    {
        $container = new ServiceContainer();

        $this->assertInstanceOf('IW\Fix\First', $container->get('IW\Fix\First'));
    }

    /**
     * Due to a bug in PHP reflection which conceal the error
     */
    public function testMakeForClassWithSyntaxError(): void
    {
        $container = new ServiceContainer();

        $this->expectException('ParseError');
        $container->make('IW\Fix\ClassWithSyntaxError');
    }

    public function testThatInterfaceCannotBeAutowired(): void
    {
        $container = new ServiceContainer();

        $this->expectException('IW\ServiceContainer\CannotAutowireInterface');
        $this->expectExceptionMessage('Cannot autowire interface: IW\Fix\Alias');

        try {
            $container->get('IW\Fix\WithAlias');
        } catch (BrokenDependency $e) {
            $this->assertSame('Getting class IW\Fix\WithAlias failed', $e->getMessage());

            throw $e->getPrevious();
        }
    }

    public function testThatScalarCannotBeAutowired(): void
    {
        $container = new ServiceContainer();

        $this->expectExceptionMessageMatches('/Unsupported type hint for param: Parameter #0 \[ <required> int(eger)? \$userId \]/');
        $container->get('IW\Fix\ClassWithUnsupportedParam');
    }

    public function testThatNoHintCannotBeAutowired(): void
    {
        $container = new ServiceContainer();

        $this->expectExceptionMessage('No type hint for param: Parameter #0 [ <required> $userId ]');
        $container->get('IW\Fix\ClassWithNoType');
    }

    public function testProperFailWhenFactoryIsDefinedBadly(): void
    {
        $container = new ServiceContainer();

        $container->bind('Poo', static function (): void {
            // this factory returns nothing
        });

        $this->expectException('IW\ServiceContainer\EmptyResultFromFactory');
        $this->expectExceptionMessage('Empty result from factory, id: Poo');
        $container->make('Poo');
    }

    public function testResolvingArbitraryFactoryParams(): void
    {
        $container = new ServiceContainer();

        $container->bind('get_me_a_foo', static function (First $first) {
            return $first;
        });

        $this->assertInstanceOf('IW\Fix\First', $container->get('get_me_a_foo'));
    }

    public function testObtainingInstance(): void
    {
        $container = new ServiceContainer();

        $this->assertNull($container->instance('IW\Fix\First'));
        $container->get('IW\Fix\First');
        $this->assertInstanceOf('IW\Fix\First', $container->instance('IW\Fix\First'));
    }

    public function testDepencyOnBrokenClass(): void
    {
        $container = new ServiceContainer();

        try {
            $container->get('IW\Fix\DependsOnClassWithFalseConstructor');
        } catch (BrokenDependency $e) {
            $this->assertSame('Getting class IW\Fix\DependsOnClassWithFalseConstructor failed', $e->getMessage());
            $this->assertInstanceOf(BrokenConstructor::class, $e->getPrevious());
            $this->assertInstanceOf('Exception', $e->getPrevious()->getPrevious());
            $this->assertSame('blah blah', $e->getPrevious()->getPrevious()->getMessage());
        }
    }

    public function testManualWiring(): void
    {
        $container = new ServiceContainer();

        $container->wire('IW\Fix\ClassWithVariadicConstructor', 'IW\Fix\Zero');

        $this->assertInstanceOf(
            'IW\Fix\ClassWithVariadicConstructor',
            $container->get('IW\Fix\ClassWithVariadicConstructor'),
        );
    }

    public function testManualWiringFail(): void
    {
        $container = new ServiceContainer();

        $container->wire('IW\Fix\ClassWithVariadicConstructor', 'IW\Fix\Zero', 'IW\Fix\First', 'IW\Fix\Second');

        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $this->expectExceptionMessage('IW\Fix\ClassWithVariadicConstructor::__construct(): Argument #2 must be of type IW\Fix\Alias, IW\Fix\First given');
        } else {
            $this->expectExceptionMessage('Argument 2 passed to IW\Fix\ClassWithVariadicConstructor::__construct() must implement interface IW\Fix\Alias, instance of IW\Fix\First given');
        }

        $this->expectException('TypeError');
        $container->get('IW\Fix\ClassWithVariadicConstructor');
    }

    public function testOptionalParams(): void
    {
        $container = new ServiceContainer();

        $instance = $container->make('IW\Fix\ClassWithOptionalParams');
        $this->assertNull($instance->fourth);
        $this->assertSame('default string', $instance->string);
        $this->assertSame([], $instance->options);

        $container->set('IW\Fix\Fourth', $container->make('IW\Fix\Fourth'));

        $instance = $container->make('IW\Fix\ClassWithOptionalParams');
        $this->assertInstanceOf('IW\Fix\Fourth', $instance->fourth);
        $this->assertSame('default string', $instance->string);
        $this->assertSame([], $instance->options);

        $container->unset('IW\Fix\Fourth');

        $instance = $container->make('IW\Fix\ClassWithOptionalParams');
        $this->assertNull($instance->fourth);
        $this->assertSame('default string', $instance->string);
        $this->assertSame([], $instance->options);
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testUnionTypes(): void
    {
        $container = new ServiceContainer();

        $this->expectException('IW\ServiceContainer\UnsupportedAutowireParam');
        $this->expectExceptionMessage('Unsupported type hint for param: Parameter #0 [ <required> IW\Fix\First|IW\Fix\Fourth $dependency ]');

        $container->get('IW\Fix\ClassWithUnionType');
    }
}
