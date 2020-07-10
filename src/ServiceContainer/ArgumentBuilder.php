<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;
use ReflectionParameter;

trait ArgumentBuilder
{
    /**
     * Returns arguments for a method/constructor
     *
     * @param string[] $ids
     *
     * @return mixed[]
     */
    final protected function buildArgs(array $ids, ServiceContainer $container): array
    {
        $args = [];

        foreach ($ids as [$id, $isOptional]) {
            if ($isOptional) {
                $arg = $container->singleton($id);

                if ($arg === null) {
                    break;
                }
            } else {
                $arg = $container->get($id);
            }

            $args[] = $arg;
        }

        return $args;
    }

    /**
     * Gets reflected parameters
     *
     * @return ReflectionParameter[]
     */
    abstract protected function getParams(): array;

    /**
     * Returns IDs of dependencies
     *
     * @return string[]
     */
    final protected function resolveIds(): array
    {
        $ids = [];

        foreach ($this->getParams() as $param) {
            $class = $param->getClass();

            if ($class === null) {
                if ($param->isOptional()) {
                    break;
                }

                throw new UnsupportedAutowireParam($param);
            }

            $ids[] = [$class->getName(), $param->isOptional()];
        }

        return $ids;
    }
}
