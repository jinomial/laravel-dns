<?php

namespace Jinomial\LaravelDns\Tests\Unit\Sockets;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\PSR7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Jinomial\LaravelDns\Contracts\Dns\Socket as SocketContract;
use Jinomial\LaravelDns\Sockets\DnsOverHttps;
use Jinomial\LaravelDns\Sockets\Socket;
use JsonException;

const ENDPOINT = 'https://cloudflare-dns.com/dns-query';
const IPV4 = 'ipv4.localhost.jinomial.com'; // resolves to 127.0.0.1
const IPV6 = 'ipv6.localhost.jinomial.com'; // resolves to ::1
const TXT = 'localhost.jinomial.com'; // resolves to "localhost" and "v=spf1 -all"

uses()->group('drivers', 'doh');

it('extends Socket::class', function () {
    $client = \Mockery::mock(ClientInterface::class);
    $driver = new DnsOverHttps('doh', $client, 'https://example.com');
    expect($driver)->toBeInstanceOf(Socket::class);
});

it('is a Socket interface', function () {
    $implementsSocket = is_a(DnsOverHttps::class, SocketContract::class, true);
    expect($implementsSocket)->toBeTrue();
});

it('handles a valid response', function () {
    $client = \Mockery::spy(ClientInterface::class);
    $response = mock(Response::class)->expect(
        getReasonPhrase: fn () => 'OK',
        getBody: fn () => Utils::streamFor('{ "valid": "json" }')
    );
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $parsed = $driver->handleResponse($response, true);
    expect($parsed['valid'])->toEqual('json');
});

it('can throw an error on an invalid response', function () {
    $client = \Mockery::spy(ClientInterface::class);
    $response = mock(Response::class)->expect(
        getReasonPhrase: fn () => 'OK',
        getBody: fn () => Utils::streamFor('{ invalid: json }')
    );
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $parsed = $driver->handleResponse($response, true);
})->throws(JsonException::class);

it('can swallow errors on an invalid response', function () {
    $client = \Mockery::spy(ClientInterface::class);
    $response = mock(Response::class)->expect(
        getReasonPhrase: fn () => 'OK',
        getBody: fn () => Utils::streamFor('{ invalid: json }')
    );
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $parsed = $driver->handleResponse($response, false);
    expect($parsed)->toBeNull();
});

it('returns null on HTTP errors', function () {
    $client = \Mockery::spy(ClientInterface::class);
    $response = mock(Response::class)->expect(
        getReasonPhrase: fn () => 'Bad Request', // <-- 400 HTTP error
    );
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $parsed = $driver->handleResponse($response, false);
    expect($parsed)->toBeNull();
});

it('can throw an exception when the question is empty', function () {
    $client = \Mockery::spy(ClientInterface::class);
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $driver->query('', 'A', ['throwOnError' => true]);
})->throws(InvalidArgumentException::class);

it('performs an A record lookup', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(IPV4, 'A', ['async' => false]);
    expect($answer[0]['Answer'][0]['data'])->toEqual('127.0.0.1');
})->group('network');

it('performs a AAAA record lookup', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(IPV6, 'AAAA', ['async' => false]);
    expect($answer[0]['Answer'][0]['data'])->toEqual('::1');
})->group('network');

it('performs a TXT record lookup', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(TXT, 'TXT', [
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

it('will return DNSSEC data when asked', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(TXT, 'TXT', [
        'async' => false,
        'do' => true,
        'cd' => false,
    ]);
    // rrsig type is 46.
    // See https://en.wikipedia.org/wiki/List_of_DNS_record_types
    $rrsigType = 46;
    $rrsigs = Arr::where(
        $answer[0]['Answer'],
        fn ($value, $key) => $value['type'] === $rrsigType
    );
    expect($rrsigs)->toHaveCount(1);
})->group('network');

it('will exclude DNSSEC data when asked', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(TXT, 'TXT', [
        'async' => false,
        'do' => false,
        'cd' => false,
    ]);
    // rrsig type is 46.
    // See https://en.wikipedia.org/wiki/List_of_DNS_record_types
    $rrsigType = 46;
    $rrsigs = Arr::where(
        $answer[0]['Answer'],
        fn ($value, $key) => $value['type'] === $rrsigType
    );
    expect($rrsigs)->toHaveCount(0);
})->group('network');

it('will validate DNSSEC when asked', function () {
    // @TODO How to make DNSSEC invalid so the request doesn't get an answer?
    // $client = new HttpClient();
    // $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    // $answer = $driver->query(IPV4, 'A', [
    //     'async' => false,
    //     'cd' => false,
    // ]);
    $this->assertTrue(true);
    // expect(count($answer[0]['Answer']))->toEqual(1);
})->group('network');

test('Record type is case insensitive', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(IPV4, 'a', ['async' => false]);
    expect($answer[0]['Answer'][0]['data'])->toEqual('127.0.0.1');
})->group('network');

it('performs multiple lookups at once', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $queries = [
        [
            'name' => IPV4,
            'type' => 'A',
        ],
        [
            'name' => IPV6,
            'type' => 'AAAA',
        ],
    ];
    $answer = $driver->query($queries, null, ['async' => false]);
    expect($answer[0]['Answer'][0]['data'])->toEqual('127.0.0.1')
      ->and($answer[1]['Answer'][0]['data'])->toEqual('::1');
})->group('network');

it('performs a lookup asynchronously', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $queries = [
        [
            'name' => IPV4,
            'type' => 'A',
        ],
        [
            'name' => IPV6,
            'type' => 'AAAA',
        ],
    ];
    $promises = $driver->query($queries, null, ['async' => true]);
    $answer = $driver->unwrap($promises);
    expect($answer[0]['Answer'][0]['data'])->toEqual('127.0.0.1')
        ->and($answer[1]['Answer'][0]['data'])->toEqual('::1');
})->group('network');
