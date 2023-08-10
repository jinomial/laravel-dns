<?php

namespace Jinomial\LaravelDns\Contracts\Dns;

interface Factory
{
    /**
     * Get a DNS socket instance by name.
     */
    public function socket(?string $name = null): Socket;
}
