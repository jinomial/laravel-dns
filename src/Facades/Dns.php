<?php

namespace Jinomial\LaravelDns\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jinomial\LaravelDns\DnsManager
 */
class Dns extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dns';
    }
}
