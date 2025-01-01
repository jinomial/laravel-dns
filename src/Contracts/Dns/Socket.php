<?php

namespace Jinomial\LaravelDns\Contracts\Dns;

interface Socket
{
    /**
     * Perform a DNS lookup.
     *
     * @api
     */
    public function query(string|array $name, string|null $type = 'A', array $options = []): array;
}
