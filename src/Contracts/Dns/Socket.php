<?php

namespace Jinomial\LaravelDns\Contracts\Dns;

interface Socket
{
    /**
     * Perform a DNS lookup.
     */
    public function query($name, $type = 'A', array $options = []);
}
