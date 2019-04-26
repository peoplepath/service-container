<?php declare(strict_types=1);

namespace IW\PHPUnit;

use IW\ServiceContainer;

trait ServiceContainerTrait
{
    private $depsProviderClass = true;

    /**
     * It's called before each test to resolve its dependencies defined
     *
     * @return void
     */
    public function ServiceContainer(string $method): array {
        return [$this->getServiceContainer()->resolve([$this, $method])];
    }

    /**
     * Resolve class's annotation @depsProvider <methodName>
     *
     * @before
     */
    public function resolveAnnotationDepsProvider(): void {
        ['class' => $classAnnotations, 'method' => $methodAnnotations] = $this->getAnnotations();

        if ($this->depsProviderClass) {
            $this->depsProviderClass = false;

            if ($classProviders = $classAnnotations['depsProvider'] ?? null)  {
                foreach ($classProviders as $classProvider) {
                    $this->callDepsProvider($classProvider);
                }
            }
        }

        if ($methodProviders = $methodAnnotations['depsProvider'] ?? null)  {
            foreach ($methodProviders as $methodProvider) {
                $this->callDepsProvider($methodProvider);
            }
        }
    }

    /**
     * Resolves and calls given deps provider
     *
     * @param string $provider method to call
     *
     * @return void
     */
    private function callDepsProvider(string $provider): void {
        $method = \get_class($this) . '::' . $provider;

        try {
            $this->$provider(...$this->getServiceContainer()->resolve([$this, $provider]));
        } catch (\TypeError $error) {
            if (!method_exists($this, $provider)) {
                throw new \InvalidArgumentException('Method ' . $method . ' does not exists');
            }

            $reflection = new \ReflectionMethod($this, $provider);
            if (!$reflection->isPublic()) {
                throw new \InvalidArgumentException('Method ' . $method . ' must be public');
            }

            throw $error;
        }
}

    /**
     * Returns instance of your service container
     *
     * @return ServiceContainer
     */
    abstract protected static function getServiceContainer(): ServiceContainer;

}