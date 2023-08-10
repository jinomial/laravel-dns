<?php

namespace Jinomial\LaravelDns\Tests\Integration\Sockets;

trait TestTrait
{
    public const ENDPOINT = 'https://cloudflare-dns.com/dns-query';
    public const IPV4 = 'ipv4.localhost.jinomial.com'; // resolves to 127.0.0.1
    public const IPV6 = 'ipv6.localhost.jinomial.com'; // resolves to ::1
    public const TXT = 'localhost.jinomial.com'; // resolves to "localhost" and "v=spf1 -all"

    // rrsig type is 46.
    // See https://en.wikipedia.org/wiki/List_of_DNS_record_types
    public const RRSIG_TYPE = 46;
}
