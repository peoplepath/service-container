<?php

namespace IW\PHPUnit;

use IW\ServiceContainer;

final class ServiceContainerTraitTest extends \PHPUnit\Framework\TestCase
{
    use ServiceContainerTrait;

    /**
     * @dataProvider ServiceContainer
     */
    function testDataProviderServiceContainer(\stdClass $myService) {
        $this->assertInstanceOf('stdClass', $myService);
    }

    /**
     * Returns instance of your service container
     *
     * @return ServiceContainer
     */
    protected function getServiceContainer(): ServiceContainer {
        return new ServiceContainer;
    }
}

