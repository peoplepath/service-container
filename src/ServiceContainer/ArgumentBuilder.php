<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

use IW\ServiceContainer;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

trait ArgumentBuilder
{
    /**
     * Returns arguments for a method/constructor
     *
     * @param array<int, array{class-string<T>, bool, mixed}> $ids
     *
     * @return T[]
     *
     * @template T
     */
    final protected function buildArgs(array $ids, ServiceContainer $container): array
    {
        $args = [];

        foreach ($ids as [$id, $isOptional, $default, $dnf]) {
            if ($isOptional) {
                $arg = $container->instance($id);

                if ($arg === null) {
                    $arg = $default;
                }
            } else {
                try {
                    $arg = $container->get($id);
                } catch (ServiceNotFound $e) {
                    $dnf ? throw new CannotAutowireCompositType($id, $e) : throw $e;
                }
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
     * @return array<int, array{class-string, bool, mixed}>
     */
    final protected function resolveIds(): array
    {
        $ids = [];

        foreach ($this->getParams() as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                /** @var class-string $classname it's not builtin so it must be classname */
                $classname = $type->getName();

                $ids[] = [
                    $classname,
                    $param->isOptional(),
                    $param->isOptional() ? $param->getDefaultValue() : null,
                    false,
                ];
                continue;
            }

            if (($type instanceof ReflectionUnionType) || ($type instanceof ReflectionIntersectionType)) {
                $ids[] = [
                    $type->__toString(),
                    $param->isOptional(),
                    $param->isOptional() ? $param->getDefaultValue() : null,
                    true,
                ];
                continue;
            }

            if ($param->isOptional()) {
                break;
            }

            throw new UnsupportedAutowireParam($param);
        }

        return $ids;
    }
}
