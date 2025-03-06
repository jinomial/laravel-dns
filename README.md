# Laravel DNS

A DNS service and facade for Laravel. Includes configurable 'doh' and 'system' drivers or create your own driver.

| Driver | Description              |
|--------|--------------------------|
| doh    | DNS over HTTPS (DoH)     |
| system | PHP's `dns_get_record()` |

## Installation

Install the package via composer:

```bash
composer require jinomial/laravel-dns
```

The default configuration is DoH through Cloudflare with Guzzle defaults:

```php
'doh' => [
    'driver' => 'doh',
    'endpoint' => env('DOH_ENDPOINT', 'https://cloudflare-dns.com/dns-query'),
    'guzzle' => [
        'connect_timeout' => 0,
        'timeout' => 0,
        'verify' => true,
    ]
],
```

Publish the config file to make changes to the configuration:
```bash
php artisan vendor:publish --provider="Jinomial\LaravelDns\DnsServiceProvider" --tag="laravel-dns-config"
```

## Usage

Query for a AAAA record using the default driver:

```php
$response = Dns::query('ipv6.localhost.jinomial.com', 'aaaa');
print_r($response);

// > Response varies by driver
```

Use a specific driver:

```php
$response = Dns::socket('system')->query('ipv4.localhost.jinomial.com', 'a');
```

### doh driver responses

The *doh* driver uses Cloudflare's DNS over HTTPs with JSON for lookups.

See the [response documentation](https://developers.cloudflare.com/1.1.1.1/encryption/dns-over-https/make-api-requests/dns-json/) for details about the response format.

```
Array
(
    [Status] => 0
    [TC] =>
    [RD] => 1
    [RA] => 1
    [AD] =>
    [CD] =>
    [Question] => Array
        (
            [0] => Array
                (
                    [name] => ipv6.localhost.jinomial.com
                    [type] => 28
                )

        )

    [Answer] => Array
        (
            [0] => Array
                (
                    [name] => ipv6.localhost.jinomial.com
                    [type] => 28
                    [TTL] => 298
                    [data] => ::1
                )

        )

)
```

### system driver responses

The *system* driver uses PHP's `dns_get_record` method for lookups.

See the [dns_get_record documentation](https://www.php.net/manual/en/function.dns-get-record.php) for details about the response format.

```
Array
(
    [0] => Array
        (
            [host] => ipv6.localhost.jinomial.com
            [class] => IN
            [ttl] => 377
            [type] => AAAA
            [ipv6] => ::1
        )

)
```

## Batch Queries

Multiple lookups can be performed at once.

```php
$response = Dns::query([
    [
        'name' => 'ipv6.localhost.jinomial.com',
        'type' => 'AAAA',
    ],
    [
        'name' => 'ipv4.localhost.jinomial.com',
        'type' => 'A',
    ],
]);
```

The *doh* driver supports asynchronous queries.

```php
$promises = Dns::query($queries, null, ['async' => true]);
$response = Dns::unwrap($promises);
```

## Custom Drivers

Create a class that extends `Jinomial\LaravelDns\Sockets\Socket`.

Implement `public function query()` according to the `Jinomial\LaravelDns\Contracts\Dns\Socket` contract.

Register a driver factory with the `Jinomial\LaravelDns\DnsManager`.

```php
    /**
     * Application service provider bootstrap for package services.
     *
     * \App\Dns\Sockets\DnsResolver is my custom driver class I made.
     * The DnsManager needs to know how to construct it.
     */
    public function boot(): void
    {
        $dnsLoader = $this->app->get(\Jinomial\LaravelDns\DnsManager::class);
        $driverName = 'my-custom-driver';
        $dnsLoader->extend($driverName, function () use ($driverName) {
            return new \App\Dns\Sockets\DnsResolver($driverName);
        });
    }
```

## Testing

Run all tests:

```bash
composer test
```

Test suites are separated into "unit" and "integration". Run each suite:

```bash
composer test:unit
composer test:integration
```

Tests are grouped into the following groups:

- network
- drivers
- doh
- system
- manager
- facades
- commands

Run tests for groups:

```bash
composer test -- --include=manager,facades
```

Network tests make remote calls that can take time or fail. Exclude them:

```bash
composer test:unit -- --exclude=network
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Jason Schmedes](https://github.com/jinomial)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
