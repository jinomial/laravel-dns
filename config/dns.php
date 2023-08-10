<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default DNS
    |--------------------------------------------------------------------------
    |
    | This option controls the default DNS socket that is used by the DNS
    | service. Alternative DNS sockets may be setup and used as needed;
    | however, this socket will be used by default.
    |
    */

    'default' => env('DNS_SOCKET', 'doh'),

    /*
    |--------------------------------------------------------------------------
    | DNS Socket Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the DNS sockets used by your application
    | plus their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Supported: "doh"
    |
    */

    'sockets' => [
        'doh' => [
            'driver' => 'doh',
            'endpoint' => env('DOH_ENDPOINT', 'https://cloudflare-dns.com/dns-query'),
            'guzzle' => [
                'connect_timeout' => 0,
                'timeout' => 0,
                'verify' => false,
            ]
        ],
        'system' => [
            'driver' => 'system',
        ],
    ],

];
