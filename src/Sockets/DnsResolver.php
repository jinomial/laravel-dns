<?php

namespace Jinomial\LaravelDns\Sockets;

use Jinomial\LaravelDns\Contracts\Dns\Socket as SocketContract;

class DnsResolver extends Socket implements SocketContract
{
    /**
     * Create a new DNS socket instance.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Perform a DNS lookup.
     */
    public function query(string|array $name, string|null $type = 'A', array $options = []): array
    {
        if (! is_array($name)) {
            $recordTypeKey = strtoupper($type ?? 'A');

            return dns_get_record($name, constant('DNS_' . $recordTypeKey));
        }

        $results = [];
        foreach ($name as $q) {
            $results[] = $this->query(
                $q['name'],
                $q['type'],
                $q['options'] ?? $options
            );
        }

        return $results;
    }
}
