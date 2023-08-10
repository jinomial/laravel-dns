<?php

namespace Jinomial\LaravelDns\Contracts\Dns;

interface Socket
{
    /**
     * Perform a DNS lookup.
     */
    public function query(string|array $name, string|null $type = 'A', array $options = []): array;
}
