# Laravel DNS

A DNS service for Laravel. Use the DNS over HTTPS (DoH) driver it includes or create your own custom driver.

## Installation

You can install the package via composer:

```bash
composer require jinomial/laravel-dns
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Jinomial\LaravelDns\DnsServiceProvider" --tag="laravel-dns-config"
```

This is the contents of the published config file:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Default DNS
    |--------------------------------------------------------------------------
    |
    | This option controls the default DNS socket that is used by the DNS
    | service. Alternative DNS sockets may be setup and used as needed;
    | however, this socket will be used by default.
    |
    */

    'default' => env('DNS_SOCKET', 'doh'),

    /*
    |--------------------------------------------------------------------------
    | DNS Socket Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the DNS sockets used by your application
    | plus their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Supported: "doh"
    |
    */

    'sockets' => [
        'doh' => [
            'driver' => 'doh',
            'endpoint' => env('DOH_ENDPOINT', 'https://cloudflare-dns.com/dns-query'),
            'guzzle' => [
                'connect_timeout' => 0,
                'timeout' => 0,
                'verify' => false,
            ]
        ],
    ],

];

```

## Usage

```php
$response = Dns::query('ipv6.localhost.jinomial.com', 'aaaa');
print_r($response);

// Array
// (
//     [Status] => 0
//     [TC] =>
//     [RD] => 1
//     [RA] => 1
//     [AD] =>
//     [CD] =>
//     [Question] => Array
//         (
//             [0] => Array
//                 (
//                     [name] => ipv6.localhost.jinomial.com
//                     [type] => 28
//                 )
//
//         )
//
//     [Answer] => Array
//         (
//             [0] => Array
//                 (
//                     [name] => ipv6.localhost.jinomial.com
//                     [type] => 28
//                     [TTL] => 298
//                     [data] => ::1
//                 )
//
//         )
//
// )
```

## Testing

Run all tests:

```bash
composer test
```

Test suites are separated into "unit" and "integration". Run each suite:

```bash
composer test-unit
composer test-integration
```

Tests are grouped into the following groups:

- network
- drivers
- doh
- manager
- facades
- commands

Run tests for groups:

```bash
composer test -- --include=manager,facades
```

Network tests make remote calls that can take time or fail. Exclude them:

```bash
composer test-unit -- --exclude=network
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
