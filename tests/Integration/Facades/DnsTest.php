<?php

namespace Jinomial\LaravelDns\Tests\Integration\Facades;

use Jinomial\LaravelDns\Facades\Dns;

const IPV6 = 'ipv6.localhost.jinomial.com'; // resolves to ::1

uses()->group('facades');

it('can resolve ' . IPV6, function () {
    $response = Dns::query(IPV6, 'aaaa');
    expect($response['Answer'][0]['data'])->toEqual('::1');
})->group('network');
