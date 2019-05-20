<?php

namespace IW\PHPUnit;

use IW\ServiceContainer;

/**
 * @depsProvider initClass
 */
final class ServiceContainerTraitTest extends \PHPUnit\Framework\TestCase
{
    use ServiceContainerTrait;

    private $classDep;

    public function initClass(\stdClass $classDep) {
        $this->classDep = $classDep;
    }

    public  function initMethod(\stdClass $methodDep) {
        $this->methodDep = $methodDep;
    }

    /**
     * @dataProvider ServiceContainer
     */
    function testDataProviderServiceContainer(\stdClass $myService) {
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

    function testClassDepsProvider() {
        $this->assertInstanceOf('stdClass', $this->classDep);
    }

    /**
     * @depsProvider initMethod
     */
    function testMethodDepsProvider() {
        $this->assertInstanceOf('stdClass', $this->methodDep);
    }

    function testUnknownMethodDepsProvider() {
        $reflectionMethod = new \ReflectionMethod($this, 'callDepsProvider');
        $reflectionMethod->setAccessible(true);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Method IW\PHPUnit\ServiceContainerTraitTest::unknown does not exists');
        $reflectionMethod->invoke($this, 'unknown');
    }

    function testProtectedMethodDepsProvider() {
        $reflectionMethod = new \ReflectionMethod($this, 'callDepsProvider');
        $reflectionMethod->setAccessible(true);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Method IW\PHPUnit\ServiceContainerTraitTest::protectedDepsProvider must be public'); // phpcs:ignore Generic.Files.LineLength
        $reflectionMethod->invoke($this, 'protectedDepsProvider');
    }

    function testBrokenDepsProvider() {
        $reflectionMethod = new \ReflectionMethod($this, 'callDepsProvider');
        $reflectionMethod->setAccessible(true);

        $this->expectException('Error');
        $this->expectExceptionMessage('Return value of IW\PHPUnit\ServiceContainerTraitTest::brokenDepsProvider() must be of the type int, string returned'); // phpcs:ignore Generic.Files.LineLength
        $reflectionMethod->invoke($this, 'brokenDepsProvider');
    }

    protected function protectedDepsProvider(ServiceContainer $container) {
        // noop
    }

    function brokenDepsProvider(): int {
        return 'string';
    }

    /**
     * Returns instance of your service container
     *
     * @return ServiceContainer
     */
    protected static function getServiceContainer(): ServiceContainer {
        return new ServiceContainer;
    }
}

