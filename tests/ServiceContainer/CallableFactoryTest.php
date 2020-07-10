<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\Fix\Fourth;
use IW\Fix\Third;
use IW\ServiceContainer;
use PHPUnit\Framework\TestCase;

use function serialize;

final class CallableFactoryTest extends TestCase
{
    public function testCreatingInstance(): void
    {
        $container = $this->createMock(ServiceContainer::class);
        $container->method('get')
            ->with('IW\Fix\Fourth')
            ->willReturn(new Fourth());

        $factory = new CallableFactory(static function (Fourth $fourth) {
            return new Third($fourth);
        });

        $this->assertInstanceOf('IW\Fix\Third', $factory($container));

        $this->expectException('IW\ServiceContainer\SerializationFail');

        serialize($factory);
    }
}
