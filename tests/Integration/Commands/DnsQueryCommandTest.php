<?php

namespace Jinomial\LaravelDns\Tests\Integration\Commands;

use Illuminate\Support\Facades\Artisan;

const IPV6 = 'ipv6.localhost.jinomial.com'; // resolves to ::1

uses()->group('commands');

test('Artisan command can resolve ' . IPV6, function () {
    // Commands that fail will exit with an exception/non-zero value.
    $exitCode = Artisan::call('dns:query', [
        'name' => IPV6,
        'type' => 'aaaa',
    ]);
    expect($exitCode)->toEqual(0);
})->group('network');
