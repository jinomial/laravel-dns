<?php

namespace Jinomial\LaravelDns\Tests\Unit\Sockets;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;
use Jinomial\LaravelDns\Contracts\Dns\Socket as SocketContract;
use Jinomial\LaravelDns\Sockets\DnsOverHttps;
use Jinomial\LaravelDns\Sockets\Socket;

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

it('performs an A record lookup', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(IPV4, 'A');
    expect($answer['Answer'][0]['data'])->toEqual('127.0.0.1');
})->group('network');

it('performs a AAAA record lookup', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(IPV6, 'AAAA');
    expect($answer['Answer'][0]['data'])->toEqual('::1');
})->group('network');

it('performs a TXT record lookup', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(TXT, 'TXT', [
        'do' => false,
        'cd' => false,
    ]);
    expect(count($answer['Answer']))->toEqual(2);
})->group('network');

it('will return DNSSEC data when asked', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(TXT, 'TXT', [
        'do' => true,
        'cd' => false,
    ]);
    // rrsig type is 46.
    // See https://en.wikipedia.org/wiki/List_of_DNS_record_types
    $rrsigType = 46;
    $rrsigs = Arr::where(
        $answer['Answer'],
        fn ($value, $key) => $value['type'] === $rrsigType
    );
    expect(count($rrsigs))->toEqual(1);
})->group('network');

it('will validate DNSSEC when asked', function () {
    // @TODO How to make DNSSEC invalid so the request doesn't get an answer?
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(IPV4, 'A', [
        'cd' => false,
    ]);
    expect(count($answer['Answer']))->toEqual(1);
})->group('network');

test('Record type is case insensitive', function () {
    $client = new HttpClient();
    $driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $answer = $driver->query(IPV4, 'a');
    expect($answer['Answer'][0]['data'])->toEqual('127.0.0.1');
})->group('network');
