<?php

namespace Jinomial\LaravelDns\Contracts\Dns;

interface Factory
{
    /**
     * Get a DNS socket instance by name.
     *
     * @param  string|null  $name
     * @return \Jinomial\LaravelDns\Contracts\Dns\Socket
     */
    public function socket($name = null);
}
