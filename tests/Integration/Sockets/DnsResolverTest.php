<?php

namespace Jinomial\LaravelDns\Tests\Integration\Sockets;

use Illuminate\Support\Arr;
use Jinomial\LaravelDns\Sockets\DnsResolver;

uses(TestTrait::class)->group('drivers', 'system');

beforeEach(function () {
    $this->driver = new DnsResolver('system');
    $this->queries = [
        [
            'name' => self::IPV4,
            'type' => 'A',
        ],
        [
            'name' => self::IPV6,
            'type' => 'AAAA',
        ],
    ];
});

it('performs an A record lookup', function () {
    $answer = $this->driver->query(self::IPV4, 'A');

    expect($answer[0]['ip'])->toEqual('127.0.0.1');
})->group('network');

it('performs a AAAA record lookup', function () {
    $answer = $this->driver->query(self::IPV6, 'AAAA');

    expect($answer[0]['ipv6'])->toEqual('::1');
})->group('network');

it('performs a TXT record lookup', function () {
    $answer = $this->driver->query(self::TXT, 'TXT', [
        'async' => false,
        'do' => false,
        'cd' => false,
    ]);


    $localhostTxt = Arr::where(
        $answer,
        fn ($result) => $result['txt'] === 'localhost'
    );

    expect($localhostTxt)->toHaveCount(1);
})->group('network');

it('returns multiple records if there are', function () {
    $answer = $this->driver->query(self::TXT, 'TXT', [
        'async' => false,
        'do' => false,
        'cd' => false,
    ]);

    expect($answer)->toHaveCount(2);
})->group('network');

test('Record type is case insensitive', function () {
    $answer = $this->driver->query(self::IPV4, 'a');

    expect($answer[0]['ip'])->toEqual('127.0.0.1');
})->group('network');

it('performs multiple lookups at once', function () {
    $answer = $this->driver->query($this->queries, null);

    expect($answer[0][0]['ip'])->toEqual('127.0.0.1')
      ->and($answer[1][0]['ipv6'])->toEqual('::1');
})->group('network');
