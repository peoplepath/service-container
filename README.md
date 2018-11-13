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


## License
All contents of this package are licensed under the [MIT license].

[Composer]: https://getcomposer.org
[MIT license]: LICENSE
