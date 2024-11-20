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

        foreach ($ids as [$id, $isOptional, $default, $dnf, $isVariadic]) {
            if ($isOptional) {
                $arg = $container->instance($id);

                if ($arg === null) {
                    if ($isVariadic) {
                        break;
                    }

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
     * @return array<int, array{class-string, bool, mixed, bool, bool}>
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
                    $param->isOptional() && ! $param->isVariadic() ? $param->getDefaultValue() : null,
                    false,
                    $param->isVariadic(),
                ];
                continue;
            }

            if (($type instanceof ReflectionUnionType) || ($type instanceof ReflectionIntersectionType)) {
                $ids[] = [
                    $type->__toString(),
                    $param->isOptional(),
                    $param->isOptional() && ! $param->isVariadic() ? $param->getDefaultValue() : null,
                    true,
                    $param->isVariadic(),
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
