<?php

declare(strict_types=1);

namespace IW\PHPUnit;

use IW\ServiceContainer;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

/**
 * @depsProvider initClass
 */
final class ServiceContainerTraitTest extends TestCase
{
    use ServiceContainerTrait;

    /** @var stdClass */
    private $classDep;

    /** @var stdClass */
    private $methodDep;

    public function initClass(stdClass $classDep) : void
    {
        $this->classDep = $classDep;
    }

    public function initMethod(stdClass $methodDep) : void
    {
        $this->methodDep = $methodDep;
    }

    /**
     * @dataProvider ServiceContainer
     */
    public function testDataProviderServiceContainer(stdClass $myService) : void
    {
        $this->assertInstanceOf('stdClass', $myService);

        $data = $this->ServiceContainer('testDataProviderServiceContainer');
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey(0, $data);
        $this->assertIsArray($data[0]);
        $this->assertCount(1, $data[0]);
        $this->assertArrayHasKey(0, $data[0]);
        $this->assertInstanceOf('stdClass', $data[0][0]);
    }

    public function testClassDepsProvider() : void
    {
        $this->assertInstanceOf('stdClass', $this->classDep);
    }

    /**
     * @depsProvider initMethod
     */
    public function testMethodDepsProvider() : void
    {
        $this->assertInstanceOf('stdClass', $this->methodDep);
    }

    public function testUnknownMethodDepsProvider() : void
    {
        $reflectionMethod = new ReflectionMethod($this, 'callDepsProvider');
        $reflectionMethod->setAccessible(true);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Method IW\PHPUnit\ServiceContainerTraitTest::unknown does not exists');
        $reflectionMethod->invoke($this, 'unknown');
    }

    public function testProtectedMethodDepsProvider() : void
    {
        $reflectionMethod = new ReflectionMethod($this, 'callDepsProvider');
        $reflectionMethod->setAccessible(true);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Method IW\PHPUnit\ServiceContainerTraitTest::protectedDepsProvider must be public'); // phpcs:ignore Generic.Files.LineLength
        $reflectionMethod->invoke($this, 'protectedDepsProvider');
    }

    public function testBrokenDepsProvider() : void
    {
        $reflectionMethod = new ReflectionMethod($this, 'callDepsProvider');
        $reflectionMethod->setAccessible(true);

        $this->expectException('Error');
        $this->expectExceptionMessage('Return value of IW\PHPUnit\ServiceContainerTraitTest::brokenDepsProvider() must be of the type int, string returned'); // phpcs:ignore Generic.Files.LineLength
        $reflectionMethod->invoke($this, 'brokenDepsProvider');
    }

    protected function protectedDepsProvider(ServiceContainer $container) : void
    {
        // noop
    }

    function brokenDepsProvider() : int
    {
        return 'string';
    }

    /**
     * Returns instance of your service container
     */
    protected static function getServiceContainer() : ServiceContainer
    {
        return new ServiceContainer();
    }
}
