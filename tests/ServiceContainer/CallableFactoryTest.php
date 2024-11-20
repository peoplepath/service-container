<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\Fix\Fourth;
use IW\Fix\Third;
use IW\ServiceContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function serialize;

final class CallableFactoryTest extends TestCase
{
    #[DataProvider('callablesProvider')]
    public function testCreatingInstanceWithClosure(callable $callable): void
    {
        $factory = new CallableFactory($callable);

        $this->assertInstanceOf('IW\Fix\Third', $factory(new ServiceContainer()));

        $this->expectException('IW\ServiceContainer\SerializationFail');
        $this->expectExceptionCode(1);

        serialize($factory);
    }

    /** @return iterable<callable> */
    public static function callablesProvider(): iterable
    {
        yield 'closure' => [static fn (Fourth $fourth) => new Third($fourth)];
        yield 'function' => [__NAMESPACE__ . '\factory_third'];
        yield 'method' => [[new FactoryThird(), 'makeThird']];
        yield 'static method' => [__NAMESPACE__ . '\FactoryThird::createThird'];
        yield 'static method 2' => [[__NAMESPACE__ . '\FactoryThird', 'createThird']];
        yield 'callable class' => [new FactoryThird()];
    }
}

// phpcs:disable

function factory_third(Fourth $fourth): Third
{
    return new Third($fourth);
}

final class FactoryThird
{
    public function __invoke(Fourth $fourth): Third
    {
        return new Third($fourth);
    }

    public static function createThird(Fourth $fourth): Third
    {
        return new Third($fourth);
    }

    public function makeThird(Fourth $fourth): Third
    {
        return new Third($fourth);
    }
}
