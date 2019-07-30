<?php

declare(strict_types=1);

namespace IW\PHPUnit;

trait ServiceContainerTrait
{
    use ServiceContainerDepsProviderTrait;
    use ServiceContainerDataProviderTrait;
}
