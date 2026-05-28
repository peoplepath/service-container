<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;
use PHPUnit\Framework\TestCase;

use function serialize;
use function unserialize;

final class ClassnameFactoryTest extends TestCase
{
    public function test_creating_instance(): void
    {
        $container = $this->createMock(ServiceContainer::class);
        $factory = new ClassnameFactory('IW\Fix\Fourth');
        $this->assertInstanceOf('IW\Fix\Fourth', $fourth = $factory($container));

        $container->expects($this->exactly(2))
            ->method('get')
            ->with('IW\Fix\Fourth')
            ->willReturn($fourth);

        $factory = new ClassnameFactory('IW\Fix\Third');
        $this->assertInstanceOf('IW\Fix\Third', $factory($container));

        $factory = unserialize(serialize($factory));
        $this->assertInstanceOf('IW\Fix\Third', $factory($container));
    }
}
