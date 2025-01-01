<?php

namespace Jinomial\LaravelDns\Tests\Integration\Facades;

use Jinomial\LaravelDns\Contracts\Dns\Socket;
use Jinomial\LaravelDns\Facades\Dns;

const IPV6 = 'ipv6.localhost.jinomial.com'; // resolves to ::1

uses()->group('facades');

it('can resolve ' . IPV6, function () {
    $response = Dns::query(IPV6, 'aaaa', ['async' => false]);

    expect($response[0]['Answer'][0]['data'])->toEqual('::1');
})->group('network');

it('can access the system driver by name', function () {
    $systemDns = Dns::socket('system');

    expect($systemDns)->toBeInstanceOf(Socket::class);
});

it('can access the doh driver by name', function () {
    $dohDns = Dns::socket('doh');

    expect($dohDns)->toBeInstanceOf(Socket::class);
});

it('can query using the system driver', function () {
    $systemDns = Dns::socket('system');
    $response = $systemDns->query(IPV6, 'aaaa', ['async' => false]);

    expect($response[0]['ipv6'])->toEqual('::1');
})->group('network');

it('can query using the doh driver', function () {
    $dohDns = Dns::socket('doh');
    $response = $dohDns->query(IPV6, 'aaaa', ['async' => false]);

    expect($response[0]['Answer'][0]['data'])->toEqual('::1');
})->group('network')->skip('doh is the default driver used in tests already');
