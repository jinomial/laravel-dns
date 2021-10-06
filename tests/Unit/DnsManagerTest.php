<?php

namespace Jinomial\LaravelDns\Tests\Unit;

use Illuminate\Config\Repository;
use InvalidArgumentException;
use Jinomial\LaravelDns\DnsManager;
use Jinomial\LaravelDns\Sockets\DnsOverHttps;
use Jinomial\LaravelDns\Sockets\Socket;

uses()->group('manager');

test('DNSManager constructs', function () {
    $app = [];
    $manager = new DnsManager($app);
    expect($manager)->toBeInstanceOf(DnsManager::class);
});

test('DNSManager can get application', function () {
    $app = [uniqid()];
    $manager = new DnsManager($app);
    expect($manager->getApplication())->toEqual($app);
});

test('DNSManager application can be set', function () {
    $app = [];
    $newApp = [uniqid()];
    $manager = new DnsManager($app);
    $manager->setApplication($newApp);
    expect($manager->getApplication())->toEqual($newApp);
});

test('DNSManager can get default socket name', function () {
    $name = uniqid();
    $config = new Repository(['dns' => ['default' => $name]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    expect($manager->getDefaultSocket())->toEqual($name);
});

test('DNSManager can set default socket name', function () {
    $name = uniqid();
    $config = new Repository(['dns' => ['default' => 'socket']]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    $manager->setDefaultSocket($name);
    expect($manager->getDefaultSocket())->toEqual($name);
});

test('DNSManager gets a socket by name', function () {
    // doh is a real driver that is created by the function
    // DnsManager::createDohDriver()
    $socketConfig = ['driver' => 'doh'];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'mysocket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    $socket = $manager->socket('mysocket');
    expect($socket)->toBeInstanceOf(Socket::class);
});

test('DNSManager supports doh driver', function () {
    $socketConfig = ['driver' => 'doh'];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'mysocket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    $socket = $manager->socket('mysocket');
    expect($socket)->toBeInstanceOf(DnsOverHttps::class);
});

test('DNSManager gets a default socket when not specified', function () {
    // doh is a real driver that is created by the function
    // DnsManager::createDohDriver()
    $socketConfig = ['driver' => 'doh'];
    $config = new Repository(['dns' => [
        'default' => 'my-default-socket',
        'sockets' => [
            'my-default-socket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    $socket = $manager->socket();
    expect($socket)->toBeInstanceOf(Socket::class);
});

test('DNSManager throws error when socket not configured', function () {
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    $socket = $manager->socket('unconfigured-socket');
})->throws(InvalidArgumentException::class);

test('DNSManager throws error when a socket driver is not supported', function () {
    // fake is a fake driver that can't be created because the function
    // DnsManager::createFakeDriver() doesn't exist.
    $socketConfig = ['driver' => 'fake'];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'defined-socket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    $socket = $manager->socket('defined-socket');
})->throws(InvalidArgumentException::class);

test('DNSManager can be extended with custom driver creators', function () {
    // custom is a driver that can't be created because the function
    // DnsManager::createCustomDriver() doesn't exist.
    $socketConfig = ['driver' => 'custom'];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'defined-socket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    // Extend the manager with a driver called 'custom'.
    $manager->extend(
        'custom',
        fn ($theApp, $theName, $theConfig) => \Mockery::spy(Socket::class)
    );
    $socket = $manager->socket('defined-socket');
    expect($socket)->toBeInstanceOf(Socket::class);
});

test('DNSManager custom creators overwrite existing creators', function () {
    // doh is a real driver that can be created because the function
    // DnsManager::createDohDriver() exists.
    $socketConfig = ['driver' => 'doh'];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'defined-socket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);
    // Extend the manager to overwrite the doh driver.
    $overwrittenSocket = 'overwritten-doh-driver';
    $manager->extend(
        'doh',
        fn ($theApp, $theName, $theConfig) => $overwrittenSocket
    );
    $socket = $manager->socket('defined-socket');
    expect($socket)->toEqual($overwrittenSocket);
});

test('DNSManager caches sockets locally by name', function () {
    // doh is a real driver that can be created because the function
    // DnsManager::createDohDriver() exists.
    $socketConfig = ['driver' => 'doh'];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'defined-socket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);

    // Get the socket so now it is cached.
    $socket = $manager->socket('defined-socket');

    // Extend the manager to overwrite the doh driver.
    $overwrittenSocket = uniqid();
    $manager->extend(
        'doh',
        fn ($theApp, $theName, $theConfig) => $overwrittenSocket
    );

    // Get the socket a second time and it should be the original and not
    // the extended one.
    expect($manager->socket('defined-socket'))->toBeInstanceOf(Socket::class);
});

test('DNSManager can purge cached sockets', function () {
    // doh is a real driver that can be created because the function
    // DnsManager::createDohDriver() exists.
    $socketConfig = ['driver' => 'doh'];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'defined-socket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);

    // Get the doh socket so now it is cached.
    $socket = $manager->socket('defined-socket');

    // Extend the manager to overwrite the doh driver.
    $overwrittenSocket = uniqid();
    $manager->extend(
        'doh',
        fn ($theApp, $theName, $theConfig) => $overwrittenSocket
    );

    // Purge the cache so the original socket should be gone
    // and getting the socket again should return the extended one.
    $manager->purge('defined-socket');

    // Get the socket a second time and it should be the original and not
    // the extended one.
    expect($manager->socket('defined-socket'))->toEqual($overwrittenSocket);
});

test('DNSManager purges by name and not driver', function () {
    // doh is a real driver that can be created because the function
    // DnsManager::createDohDriver() exists.
    $socketConfig = ['driver' => 'doh'];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'defined-socket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);

    // Get the socket so now it is cached.
    $socket = $manager->socket('defined-socket');

    // Extend the manager to overwrite the doh driver creator.
    $overwrittenSocket = uniqid();
    $manager->extend(
        'doh',
        fn ($theApp, $theName, $theConfig) => $overwrittenSocket
    );

    // Try to purge the cache by driver instead of socket name.
    // The original socket should still be there and getting the socket
    // again should return the cached one.
    $manager->purge('doh');

    // Get the socket a second time and it should be the original and not
    // the extended one.
    expect($manager->socket('defined-socket'))->toBeInstanceOf(Socket::class);
});

test('DNSManager can forget all sockets', function () {
    $driver1 = uniqid();
    $driver2 = uniqid();
    $socketConfig1 = ['driver' => $driver1];
    $socketConfig2 = ['driver' => $driver2];
    $config = new Repository(['dns' => [
        'default' => 'no-default-socket',
        'sockets' => [
            'defined-socket-1' => $socketConfig1,
            'defined-socket-2' => $socketConfig2,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);

    // Extend the manager to support the custom drivers.
    $manager->extend(
        $driver1,
        fn ($theApp, $theName, $theConfig) => $driver1
    );
    $manager->extend(
        $driver2,
        fn ($theApp, $theName, $theConfig) => $driver2
    );

    // Get both sockets so now both are cached.
    $manager->socket('defined-socket-1');
    $manager->socket('defined-socket-2');

    // Extend the manager to overwrite the driver creators.
    $newSocket1 = uniqid();
    $manager->extend(
        $driver1,
        fn ($theApp, $theName, $theConfig) => $newSocket1
    );
    $newSocket2 = uniqid();
    $manager->extend(
        $driver2,
        fn ($theApp, $theName, $theConfig) => $newSocket2
    );

    // Forget all sockets so getting the sockets again should use the new
    // creators instead of getting the sockets from the cache.
    $manager->forgetSockets();

    // Get the sockets a second time and it should be the new sockets.
    expect($manager->socket('defined-socket-1'))->toEqual($newSocket1)
        ->and($manager->socket('defined-socket-2'))->toEqual($newSocket2);
});

test('DNSManager can dynamically call the default driver', function () {
    $driver = uniqid();
    $socketConfig = ['driver' => $driver];
    // The socket that will be used is the default socket "default-socket".
    $config = new Repository(['dns' => [
        'default' => 'default-socket',
        'sockets' => [
            'default-socket' => $socketConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new DnsManager($app);

    // Extend the manager to support the custom driver.
    $dynamicCallResponse = uniqid();
    $socket = mock(Socket::class)->expect(
        mycall: fn () => $dynamicCallResponse,
    );
    $manager->extend(
        $driver,
        fn ($theApp, $theName, $theConfig) => $socket
    );

    // mycall should be called magically on the default socket.
    // If it is it will satisfy the expectation on the mock socket.
    $manager->mycall();
});
