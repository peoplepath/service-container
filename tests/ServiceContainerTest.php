<?php

declare(strict_types=1);

// phpcs:disable Generic.Files.LineLength.TooLong

namespace IW;

use const PHP_VERSION;

use IW\Fix\First;
use IW\Fix\Fourth;
use IW\ServiceContainer\BrokenConstructor;
use IW\ServiceContainer\BrokenDependency;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

use function random_bytes;
use function uniqid;
use function version_compare;

class ServiceContainerTest extends TestCase
{
    public function test_getting_non_existing_class(): void
    {
        $container = new ServiceContainer;

        $this->expectException('IW\ServiceContainer\ServiceNotFound');
        $this->expectExceptionCode(1);
        $this->expectExceptionMessage('Service object not found, id: IW\NotExists');
        $container->get('IW\NotExists');
    }

    public function test_that_exception_not_related_with_service_making_are_disclosed(): void
    {
        $container = new ServiceContainer;

        $this->expectException('Exception');
        $this->expectExceptionMessage('blah blah');

        try {
            $container->get('IW\Fix\ClassWithFalseConstructor');
        } catch (BrokenConstructor $e) {
            $this->assertSame($e->getMessage(), 'Constructor class IW\Fix\ClassWithFalseConstructor failed');

            throw $e->getPrevious();
        }
    }

    public function test_setting_and_unsetting_a_service(): void
    {
        $container = new ServiceContainer;

        $service = new stdClass;
        $container->set($id = random_bytes(10), $service);

        $this->assertSame($service, $container->get($id));

        $this->assertSame($service, $container->unset($id));

        $this->expectException('IW\ServiceContainer\ServiceNotFound');
        $this->expectExceptionMessage('Service object not found, id: '.$id);
        $container->get($id);
    }

    public function test_unsetting_unknown_service(): void
    {
        $container = new ServiceContainer;

        $this->assertNull($container->unset('ImNotSingleton'));
    }

    public function test_implicit_singleton(): void
    {
        $container = new ServiceContainer;

        // you always get singleton of same class (id)
        $service = $container->get('IW\Fix\First');
        $this->assertSame($service, $container->get('IW\Fix\First'));
    }

    public function test_service_aliasing(): void
    {
        $container = new ServiceContainer;

        $container->alias('IW\Fix\Alias', 'IW\Fix\Zero');
        $this->assertSame($container->get('IW\Fix\Alias'), $container->get('IW\Fix\Zero'));
    }

    public function test_binding_custom_factory(): void
    {
        $container = new ServiceContainer;

        $bar = $container->get('IW\Fix\Second');

        $container->bind($id = uniqid(), static function (ServiceContainer $container) use ($bar) {
            $service = new stdClass;
            $service->foo = $container->get('IW\Fix\First');
            $service->bar = $bar;

            return $service;
        });

        $service = $container->get($id);

        $this->assertIsObject($service);
        $this->assertInstanceOf('stdClass', $service);
        $this->assertObjectHasProperty('foo', $service);
        $this->assertInstanceOf('IW\Fix\First', $service->foo);
        $this->assertObjectHasProperty('bar', $service);
        $this->assertSame($bar, $service->bar);
    }

    #[TestWith(['IW\\NotExists', false])]
    #[TestWith(['IW\\Fix\\Fourth', true])]
    #[TestWith(['IW\\Fix\\Third', true])]
    public function test_has_method(string $id, bool $has): void
    {
        $container = $this->createPartialMock(ServiceContainer::class, ['make']);
        $container->expects($this->never())->method('make');

        if ($has) {
            $this->assertTrue($container->has($id));
        } else {
            $this->assertFalse($container->has($id));
        }
    }

    public function test_has_a_singleton(): void
    {
        $container = $this->createPartialMock(ServiceContainer::class, ['make', 'factory']);
        $container->expects($this->never())->method('make');
        $container->expects($this->never())->method('factory');

        $container->set('aclass', new stdClass);
        $this->assertTrue($container->has('aclass'));
    }

    public function test_has_a_factory(): void
    {
        $container = $this->createPartialMock(ServiceContainer::class, ['make', 'factory']);
        $container->expects($this->never())->method('make');
        $container->expects($this->never())->method('factory');

        $container->bind('aclass', static function () {
            return new stdClass;
        });
        $this->assertTrue($container->has('aclass'));
    }

    public function test_get_for_build_in_class(): void
    {
        $container = new ServiceContainer;

        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    public function test_get_for_a_class(): void
    {
        $container = new ServiceContainer;

        $this->assertInstanceOf('IW\Fix\First', $container->get('IW\Fix\First'));
    }

    /**
     * Due to a bug in PHP reflection which conceal the error
     */
    public function test_make_for_class_with_syntax_error(): void
    {
        $container = new ServiceContainer;

        $this->expectException('ParseError');
        $container->make('IW\Fix\ClassWithSyntaxError');
    }

    public function test_that_interface_cannot_be_autowired(): void
    {
        $container = new ServiceContainer;

        $this->expectException('IW\ServiceContainer\CannotAutowireInterface');
        $this->expectExceptionMessage('Cannot autowire interface: IW\Fix\Alias');

        try {
            $container->get('IW\Fix\WithAlias');
        } catch (BrokenDependency $e) {
            $this->assertSame('Getting class IW\Fix\WithAlias failed', $e->getMessage());

            throw $e->getPrevious();
        }
    }

    public function test_that_scalar_cannot_be_autowired(): void
    {
        $container = new ServiceContainer;

        $this->expectExceptionMessageMatches('/Unsupported type hint for param: Parameter #0 \[ <required> int(eger)? \$userId \]/');
        $container->get('IW\Fix\ClassWithUnsupportedParam');
    }

    public function test_that_no_hint_cannot_be_autowired(): void
    {
        $container = new ServiceContainer;

        $this->expectExceptionMessage('No type hint for param: Parameter #0 [ <required> $userId ]');
        $container->get('IW\Fix\ClassWithNoType');
    }

    public function test_proper_fail_when_factory_is_defined_badly(): void
    {
        $container = new ServiceContainer;

        $container->bind('Poo', static function (): void {
            // this factory returns nothing
        });

        $this->expectException('IW\ServiceContainer\EmptyResultFromFactory');
        $this->expectExceptionMessage('Empty result from factory, id: Poo');
        $container->make('Poo');
    }

    public function test_resolving_arbitrary_factory_params(): void
    {
        $container = new ServiceContainer;

        $container->bind('get_me_a_foo', static function (First $first) {
            return $first;
        });

        $this->assertInstanceOf('IW\Fix\First', $container->get('get_me_a_foo'));
    }

    public function test_obtaining_instance(): void
    {
        $container = new ServiceContainer;

        $this->assertNull($container->instance('IW\Fix\First'));
        $container->get('IW\Fix\First');
        $this->assertInstanceOf('IW\Fix\First', $container->instance('IW\Fix\First'));
    }

    public function test_depency_on_broken_class(): void
    {
        $container = new ServiceContainer;

        try {
            $container->get('IW\Fix\DependsOnClassWithFalseConstructor');
        } catch (BrokenDependency $e) {
            $this->assertSame('Getting class IW\Fix\DependsOnClassWithFalseConstructor failed', $e->getMessage());
            $this->assertInstanceOf(BrokenConstructor::class, $e->getPrevious());
            $this->assertSame('Constructor class IW\Fix\ClassWithFalseConstructor failed', $e->getPrevious()->getMessage());
            $this->assertInstanceOf('Exception', $e->getPrevious()->getPrevious());
            $this->assertSame('blah blah', $e->getPrevious()->getPrevious()->getMessage());
        }
    }

    public function test_depency_on_depends_on_broken_class(): void
    {
        $container = new ServiceContainer;

        try {
            $container->get('IW\Fix\DependsOnDependsOnClassWithFalseConstructor');
        } catch (BrokenDependency $e) {
            $this->assertSame('Getting class IW\Fix\DependsOnDependsOnClassWithFalseConstructor failed', $e->getMessage());
            $this->assertInstanceOf(BrokenDependency::class, $e->getPrevious());
            $this->assertSame('Getting class IW\Fix\DependsOnClassWithFalseConstructor failed', $e->getPrevious()->getMessage());
            $this->assertInstanceOf('IW\ServiceContainer\BrokenConstructor', $e->getPrevious()->getPrevious());
            $this->assertSame('Constructor class IW\Fix\ClassWithFalseConstructor failed', $e->getPrevious()->getPrevious()->getMessage());
            $this->assertInstanceOf('Exception', $e->getPrevious()->getPrevious());
            // $this->assertSame('blah blah', $e->getPrevious()->getPrevious()->getMessage());
        }
    }

    public function test_manual_wiring(): void
    {
        $container = new ServiceContainer;

        $container->wire('IW\Fix\ClassWithVariadicConstructor', 'IW\Fix\Zero');

        $this->assertInstanceOf(
            'IW\Fix\ClassWithVariadicConstructor',
            $container->get('IW\Fix\ClassWithVariadicConstructor'),
        );
    }

    public function test_manual_wiring_fail(): void
    {
        $container = new ServiceContainer;

        $container->wire('IW\Fix\ClassWithVariadicConstructor', 'IW\Fix\Zero', 'IW\Fix\First', 'IW\Fix\Second');

        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $this->expectExceptionMessage('IW\Fix\ClassWithVariadicConstructor::__construct(): Argument #2 must be of type IW\Fix\Alias, IW\Fix\First given');
        } else {
            $this->expectExceptionMessage('Argument 2 passed to IW\Fix\ClassWithVariadicConstructor::__construct() must implement interface IW\Fix\Alias, instance of IW\Fix\First given');
        }

        $this->expectException('TypeError');
        $container->get('IW\Fix\ClassWithVariadicConstructor');
    }

    public function test_variadic_constructor(): void
    {
        $container = new ServiceContainer;
        $this->assertEmpty($container->make('IW\Fix\ClassWithVariadicConstructor')->deps);

        $container->set('IW\Fix\Alias', $zero = $container->get('IW\Fix\Zero'));
        $this->assertSame([$zero], $container->make('IW\Fix\ClassWithVariadicConstructor')->deps);
    }

    public function test_optional_params(): void
    {
        $container = new ServiceContainer;

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

    #[RequiresPhp('>= 8.0.0')]
    public function test_union_types_wiring(): void
    {
        $container = new ServiceContainer;

        $container->wire('IW\Fix\ClassWithUnionType', 'IW\Fix\First');

        $this->assertInstanceOf('IW\Fix\ClassWithUnionType', $container->get('IW\Fix\ClassWithUnionType'));
    }

    #[RequiresPhp('>= 8.0.0')]
    public function test_union_types_cannot_be_autowired(): void
    {
        $container = new ServiceContainer;

        try {
            $container->get('IW\Fix\ClassWithUnionType');
        } catch (Throwable $e) {
            $this->assertInstanceOf('IW\ServiceContainer\BrokenDependency', $e);
            $this->assertInstanceOf('IW\ServiceContainer\CannotAutowireCompositType', $e->getPrevious());
            $this->assertSame('Cannot autowire composit type: IW\Fix\First|IW\Fix\Fourth, define a factory for it', $e->getPrevious()->getMessage());
        }
    }

    #[RequiresPhp('>= 8.1.0')]
    public function test_intersection_types_wiring(): void
    {
        $container = new ServiceContainer;

        $container->wire('IW\Fix\ClassWithIntersectionType', 'IW\Fix\Zero');

        $this->assertInstanceOf('IW\Fix\ClassWithIntersectionType', $container->get('IW\Fix\ClassWithIntersectionType'));
    }

    #[RequiresPhp('>= 8.1.0')]
    public function test_intersection_types_cannot_be_autowired(): void
    {
        $container = new ServiceContainer;

        try {
            $container->get('IW\Fix\ClassWithIntersectionType');
        } catch (Throwable $e) {
            $this->assertInstanceOf('IW\ServiceContainer\BrokenDependency', $e);
            $this->assertInstanceOf('IW\ServiceContainer\CannotAutowireCompositType', $e->getPrevious());
            $this->assertSame('Cannot autowire composit type: IW\Fix\Alias&IW\Fix\Zero, define a factory for it', $e->getPrevious()->getMessage());
        }
    }

    public function test_nullable_param(): void
    {
        $container = new ServiceContainer;
        $container->set('IW\Fix\First', null);
        $this->assertTrue($container->has('IW\Fix\First'));

        $instance = $container->make('IW\Fix\ClassWithNullableParam');
        $this->assertNull($instance->first);
    }

    public function test_factory(): void
    {
        $container = new ServiceContainer;

        $this->assertInstanceOf('IW\ServiceContainer\ClassnameFactory', $container->factory(Fourth::class));

        $container->bind(Fourth::class, static fn () => new Fourth);

        $this->assertInstanceOf('IW\ServiceContainer\CallableFactory', $container->factory(Fourth::class));
    }

    public function test_try(): void
    {
        $container = new ServiceContainer;

        $this->assertInstanceOf('IW\Fix\First', $container->try('IW\Fix\First'));
        $this->assertNull($container->try('IW\Fix\ClassWithFalseConstructor', $exception));
        $this->assertInstanceOf('Psr\Container\ContainerExceptionInterface', $exception);
        $this->assertInstanceOf('Exception', $exception->getPrevious());

        $this->expectException('ParseError');
        $this->assertNull($container->try('IW\Fix\ClassWithSyntaxError'));
    }

    #[RequiresPhp('>= 8.4.0')]
    public function test_lazy(): void
    {
        $container = new ServiceContainer;

        $initialized = false;

        $container->lazy(Fix\ClassWithSomethingToSay::class, static function () use (&$initialized) {
            $initialized = true;

            return new Fix\ClassWithSomethingToSay('I toast therefore I am');
        });

        $toaster = $container->get(Fix\ClassWithSomethingToSay::class);

        $this->assertInstanceOf(Fix\ClassWithSomethingToSay::class, $toaster);
        $this->assertFalse($initialized);

        $this->assertSame('I toast therefore I am', $toaster->say());
        $this->assertTrue($initialized);
    }

    #[RequiresPhp('>= 8.2.0')]
    public function test_bind_dnf_type(): void
    {
        $container = new ServiceContainer;

        $container->bind('(IW\Fix\Zero&IW\Fix\Alias)|IW\Fix\Fourth', static fn () => $container->get('IW\Fix\Zero'));

        $this->assertInstanceOf('IW\Fix\ClassWithDnfType', $container->make('IW\Fix\ClassWithDnfType'));
    }

    #[RequiresPhp('>= 8.2.0')]
    public function test_alias_dnf_type(): void
    {
        $container = new ServiceContainer;

        $container->alias('(IW\Fix\Zero&IW\Fix\Alias)|IW\Fix\Fourth', 'IW\Fix\Fourth');

        $this->assertInstanceOf('IW\Fix\ClassWithDnfType', $container->make('IW\Fix\ClassWithDnfType'));
    }

    #[RequiresPhp('>= 8.1.0')]
    public function test_new_inicializer(): void
    {
        $container = new ServiceContainer;

        $this->assertInstanceOf(Fix\Zero::class, $container->make(Fix\ClassWithNewInicializer::class)->alias);
    }
}
