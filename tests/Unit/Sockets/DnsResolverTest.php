<?php

namespace Jinomial\LaravelDns\Tests\Unit\Sockets;

use Jinomial\LaravelDns\Contracts\Dns\Socket as SocketContract;
use Jinomial\LaravelDns\Sockets\DnsResolver;
use Jinomial\LaravelDns\Sockets\Socket;

uses()->group('drivers', 'system');

beforeEach(function () {
    $this->driver = new DnsResolver('system');
});

it('extends Socket::class', function () {
    expect($this->driver)->toBeInstanceOf(Socket::class);
});

it('is a Socket interface', function () {
    $implementsSocket = is_a(DnsResolver::class, SocketContract::class, true);

    expect($implementsSocket)->toBeTrue();
});
