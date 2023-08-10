<?php

namespace Jinomial\LaravelDns\Tests\Integration\Sockets;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Arr;
use Jinomial\LaravelDns\Sockets\DnsOverHttps;

uses(TestTrait::class)->group('drivers', 'doh');

beforeEach(function () {
    $client = new HttpClient();
    $this->driver = new DnsOverHttps('doh', $client, self::ENDPOINT);
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
    $answer = $this->driver->query(self::IPV4, 'A', ['async' => false]);

    expect($answer[0]['Answer'][0]['data'])->toEqual('127.0.0.1');
})->group('network');

it('performs a AAAA record lookup', function () {
    $answer = $this->driver->query(self::IPV6, 'AAAA', ['async' => false]);

    expect($answer[0]['Answer'][0]['data'])->toEqual('::1');
})->group('network');

it('performs a TXT record lookup', function () {
    $answer = $this->driver->query(self::TXT, 'TXT', [
        'async' => false,
        'do' => false,
        'cd' => false,
    ]);
    $localhostTxt = Arr::where(
        $answer[0]['Answer'],
        fn ($value) => $value['data'] === '"localhost"'
    );

    expect($localhostTxt)->toHaveCount(1);
})->group('network');

test('Record type is case insensitive', function () {
    $answer = $this->driver->query(self::IPV4, 'a', ['async' => false]);

    expect($answer[0]['Answer'][0]['data'])->toEqual('127.0.0.1');
})->group('network');

it('performs multiple lookups at once', function () {
    $answer = $this->driver->query($this->queries, null, ['async' => false]);

    expect($answer[0]['Answer'][0]['data'])->toEqual('127.0.0.1')
      ->and($answer[1]['Answer'][0]['data'])->toEqual('::1');
})->group('network');

it('performs a lookup asynchronously', function () {
    $promises = $this->driver->query($this->queries, null, ['async' => true]);
    $answer = $this->driver->unwrap($promises);

    expect($answer[0]['Answer'][0]['data'])->toEqual('127.0.0.1')
        ->and($answer[1]['Answer'][0]['data'])->toEqual('::1');
})->group('network');
