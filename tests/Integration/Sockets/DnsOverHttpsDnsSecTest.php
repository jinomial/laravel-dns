<?php

namespace Jinomial\LaravelDns\Tests\Integration\Sockets;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Arr;
use Jinomial\LaravelDns\Sockets\DnsOverHttps;

uses(TestTrait::class)->group('drivers', 'doh', 'dnssec');

beforeEach(function () {
    $client = new HttpClient();
    $this->driver = new DnsOverHttps('doh', $client, self::ENDPOINT);
});

it('will return DNSSEC data when asked', function () {
    $answer = $this->driver->query(self::TXT, 'TXT', [
        'async' => false,
        'do' => true,
        'cd' => false,
    ]);
    $rrsigs = Arr::where(
        $answer[0]['Answer'],
        fn ($value, $key) => $value['type'] === self::RRSIG_TYPE
    );

    expect($rrsigs)->toHaveCount(1);
})->group('network');

it('will exclude DNSSEC data when asked', function () {
    $answer = $this->driver->query(self::TXT, 'TXT', [
        'async' => false,
        'do' => false,
        'cd' => false,
    ]);
    $rrsigs = Arr::where(
        $answer[0]['Answer'],
        fn ($value, $key) => $value['type'] === self::RRSIG_TYPE
    );

    expect($rrsigs)->toHaveCount(0);
})->group('network');

it('will validate DNSSEC when asked', function () {
    $answer = $this->driver->query(self::IPV4, 'A', [
        'async' => false,
        'cd' => false,
    ]);

    expect(count($answer[0]['Answer']))->toEqual(1);
})->group('network')->skip('How to make DNSSEC invalid?');
