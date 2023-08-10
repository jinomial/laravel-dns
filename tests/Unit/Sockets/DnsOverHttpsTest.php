<?php

namespace Jinomial\LaravelDns\Tests\Unit\Sockets;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Jinomial\LaravelDns\Contracts\Dns\Socket as SocketContract;
use Jinomial\LaravelDns\Sockets\DnsOverHttps;
use Jinomial\LaravelDns\Sockets\Socket;
use JsonException;
use Mockery;

const ENDPOINT = 'https://example.com';

uses()->group('drivers', 'doh');

beforeEach(function () {
    $client = Mockery::spy(ClientInterface::class);
    $this->driver = new DnsOverHttps('doh', $client, ENDPOINT);
    $this->response = Mockery::mock(Response::class);
    $this->validResponse = Utils::streamFor('{ "valid": "json" }');
    $this->invalidResponse = Utils::streamFor('{ invalid: json }');
});

it('extends Socket::class', function () {
    expect($this->driver)->toBeInstanceOf(Socket::class);
});

it('is a Socket interface', function () {
    $implementsSocket = is_a(DnsOverHttps::class, SocketContract::class, true);

    expect($implementsSocket)->toBeTrue();
});

it('handles a valid response', function () {
    $this->response->shouldReceive('getReasonPhrase')->andReturn('OK');
    $this->response->shouldReceive('getBody')->andReturn($this->validResponse);

    $parsed = $this->driver->handleResponse($this->response, true);

    expect($parsed['valid'])->toEqual('json');
});

it('can throw an error on an invalid response', function () {
    $this->response->shouldReceive('getReasonPhrase')->andReturn('OK');
    $this->response->shouldReceive('getBody')->andReturn($this->invalidResponse);

    $parsed = $this->driver->handleResponse($this->response, true);
})->throws(JsonException::class);

it('can swallow errors on an invalid response', function () {
    $this->response->shouldReceive('getReasonPhrase')->andReturn('OK');
    $this->response->shouldReceive('getBody')->andReturn($this->invalidResponse);

    $parsed = $this->driver->handleResponse($this->response, false);

    expect($parsed)->toBeNull();
});

it('returns null on HTTP errors', function () {
    $this->response->shouldReceive('getReasonPhrase')->andReturn('Bad Request');

    $parsed = $this->driver->handleResponse($this->response, false);

    expect($parsed)->toBeNull();
});

it('can throw an exception when the question is empty', function () {
    $this->driver->query('', 'A', ['throwOnError' => true]);
})->throws(InvalidArgumentException::class);
