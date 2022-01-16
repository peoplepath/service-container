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

        foreach ($ids as [$id, $isOptional, $default]) {
            if ($isOptional) {
                $arg = $container->instance($id);

                if ($arg === null) {
                    $arg = $default;
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
    abstract private function getParams(): array;

    /**
     * Returns IDs of dependencies
     *
     * @return string[]
     */
    final protected function resolveIds(): array
    {
        $ids = [];

        foreach ($this->getParams() as $param) {
            $type = $param->getType();

            if ($type === null || $type->isBuiltin()) {
                if ($param->isOptional()) {
                    break;
                }

                throw new UnsupportedAutowireParam($param);
            }

            $ids[] = [$type->getName(), $param->isOptional(), $param->isOptional() ? $param->getDefaultValue() : null];
        }

        return $ids;
    }
}
