<?php

declare(strict_types=1);

namespace IW\ServiceContainer;

final class FactoryContainer
{
    /** @var ServiceFactory[] */
    private $factories = [];

    public function alias(string $alias, string $id): void
    {
        $this->factories[$alias] = new AliasFactory($id);
    }

    public function bind(string $id, callable $factory): void
    {
        $this->factories[$id] = new CallableFactory($factory);
    }

    public function get(string $id): ServiceFactory
    {
        if (isset($this->factories[$id])) {
            return $this->factories[$id];
        }

        return $this->factories[$id] = new ClassnameFactory($id);
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }
}
