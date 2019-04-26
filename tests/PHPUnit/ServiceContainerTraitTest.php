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

    /**
     * Returns instance of your service container
     *
     * @return ServiceContainer
     */
    protected static function getServiceContainer(): ServiceContainer {
        return new ServiceContainer;
    }
}

