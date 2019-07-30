<?php

declare(strict_types=1);

namespace IW\PHPUnit;

use Error;
use InvalidArgumentException;
use ReflectionMethod;
use function method_exists;

trait ServiceContainerDepsProviderTrait
{
    use ServiceContainerProviderTrait;

    private $depsProviderClass = true;

    /**
     * Resolve class's annotation @depsProvider <methodName>
     *
     * @before
     */
    public function resolveAnnotationDepsProvider() : void
    {
        ['class' => $classAnnotations, 'method' => $methodAnnotations] = $this->getAnnotations();

        if ($this->depsProviderClass) {
            $this->depsProviderClass = false;

            if ($classProviders = $classAnnotations['depsProvider'] ?? null) {
                foreach ($classProviders as $classProvider) {
                    $this->callDepsProvider($classProvider);
                }
            }
        }

        if (! $methodProviders = $methodAnnotations['depsProvider'] ?? null) {
            return;
        }

        foreach ($methodProviders as $methodProvider) {
            $this->callDepsProvider($methodProvider);
        }
    }

    /**
     * Resolves and calls given deps provider
     *
     * @param string $provider method to call
     */
    private function callDepsProvider(string $provider) : void
    {
        $method = static::class . '::' . $provider;

        try {
            $this->$provider(...$this->getServiceContainer()->resolve([$this, $provider]));
        } catch (Error $error) {
            if (! method_exists($this, $provider)) {
                throw new InvalidArgumentException('Method ' . $method . ' does not exists');
            }

            $reflection = new ReflectionMethod($this, $provider);
            if (! $reflection->isPublic()) {
                throw new InvalidArgumentException('Method ' . $method . ' must be public');
            }

            throw $error;
        }
    }
}
