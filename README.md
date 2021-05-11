Lightweight yet powerful implementation of dependancy injection container with autowiring.

## Installation
Use [Composer] to install the package:
```
composer require intraworlds/service-container
```

## Usage
The container is implementing standard PSR-11 interface. You can use it with autowiring out-of-the-box.

Let's say that we're building a cache client. This client will not implement a cache directly but only provides common API - it will depend on given adapter.

We'll start will start with defining memory adapter
```php
namespace Acme\Cache;

class MemoryAdapter {
  function get(string $key): string {}
  function set(string $key, string $string): void {}
}
```
Cache client
```php
namespace Acme\Cache;

class Client {
  private $adapter;

  function __construct(MemoryAdapter $adapter) {
    $this->adapter = $adapter;
  }

  function get(string $key) {
    $string = $this->adapter->get($key);
    return unserialize($string);
  }

  function set(string $key, $value): void {
    $string = serialize($value);
    $this->adapter->set($key, $string);
  }
}
```
Now in the main program we can instantiate cache client with ease - dependancy of the client will be resolved automatically.
```php
namespace Acme;

use IW\ServiceContainer;

$container = new ServiceContainer;
$client = $container->get(Cache\Client::class);
```
Our still implementing serialization by itself. Let's move it into dependancy.
```php
namespace Acme\Cache;

class PhpSerializer {
  function serialize($value): string {}
  function unserialize(string $string) {}
}
```
And little change in the client.
```php
namespace Acme\Cache;

class Client {
  private $adapter;

  function __construct(MemoryAdapter $adapter, PhpSerializer $serializer) {
    $this->adapter = $adapter;
    $this->serializer = $serializer;
  }

  function get(string $key) {
    $string = $this->adapter->get($key);
    return $this->serializer->unserialize($string);
  }

  function set(string $key, $value): void {
    $string = $this->serializer->serialize($value);
    $this->adapter->set($key, $string);
  }
}
```
Our main code will stay the same.
```php
$client = $container->get(Cache\Client::class);
```
Method `resolve` is useful for resolving any dependencies of a _callable_. Especially it's useful for `init` template method. See following example.
```php
abstract class Parent {
  function __construct(Dependency $dependency, ServiceContainer $container) {
    $this->dependency = $dependency;
    $container->resolve([$this, 'init']);
  }
}

class Child extends Parent {
  function init(AhotherDependency $another) {
    // ...
  }
}
```

#### Manual Wiring
Sometimes you want to configure container manually. Let's consider following example on _Command Pattern_
```php
interface OrderCommand
{
  function execute();
}

class OrderInvoker
{
  function __construct(private OrderCommand ...$commands) {}

  function execute() : void {
    array_walk($this->commands, fn($command) => $command->execute());
  }
}
```
With `IW\ServiceContainer` you have several options how to resolve `OrderInvoker`'s dependencies.
```php
// an alias but that's no good for multiple commands
$container->alias('OrderInvoker', 'ReserveItems');

// external factory
$container->bind('OrderInvoker', function (IW\ServiceContainer $container) {
  return new OrderInvoker($container->get('ReserveItems'), $container->get('SendInvoice'));
});

// internal factory
$container->bind('OrderInvoker', 'OrderInvoker::create');

class OrderInvoker
{
  static function create(ReserveItems $reserveItems, SendInvoice $sendInvoice) : OrderInvoker {
    return new OrderInvoker($reserveItems, $sendInvoice);
  }
}

// wiring factory
$container->wire('OrderInvoker', 'ReserveItems', 'SendInvoice');

// using annotations (TBD PHP 8.0), used as a fallback (can be overriden by defining factory directly (eg. in tests)
class OrderInvoker
{
  #[IW\ServiceContainer\Bind('create')]
  function __construct(private OrderCommand ...$commands) {}

  static function create(ReserveItems $reserveItems, SendInvoice $sendInvoice) : OrderInvoker {
    return new OrderInvoker($reserveItems, $sendInvoice);
  } 
}
```
Arguably all approaches have their advantages. _internal factory_ approach is
good for static analysis. _wiring factory_ is useful for common application pattern
and when dependencies may vary.

**TODO** keep going with examples

## TODO
```php
$exception->getOrigin(); // returns first exception outside the framework (useful for avoiding of tracing)
```

## License
All contents of this package are licensed under the [MIT license].

[Composer]: https://getcomposer.org
[MIT license]: LICENSE
