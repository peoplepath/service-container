<?php declare(strict_types=1);

namespace IW\PHPUnit;

use IW\ServiceContainer;

trait ServiceContainerTrait
{
    use ServiceContainerDepsProviderTrait;
    use ServiceContainerDataProviderTrait;
}
